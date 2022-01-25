<?php
namespace App\Controller;

use App\Entity\CacheApi;
use App\Entity\Character;
use App\Entity\Film;
use App\Repository\FilmRepository;
use Doctrine\Persistence\ManagerRegistry;
use Http\Discovery\HttpAsyncClientDiscovery;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppController  extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route(name="home", path="/")
     */
    public function home(): Response
    {
        return $this->render('app/home.html.twig', [
            'number' => 10,
        ]);
    }

    /**
     * @Route(name="clear_cache", path="/clear-cache")
     */
    public function clearCache(ManagerRegistry $doctrine): Response{
        $doctrine->getRepository(CacheApi::class)->deleteAll();
        return $this->render('cache/index.html.twig');
    }

    /**
     * @Route(name="character_single", path="/character/{id}")
     */
    public function character(ManagerRegistry $doctrine, $id): Response{
        $character = $doctrine->getRepository(Character::class)->findFilms($id);
        $SWCharApiPath = $character->getApiUrl();
        $films = [];
        try {
            $httpClient = new HttplugClient();
            $request = $httpClient->createRequest('GET', $SWCharApiPath);
            $promise = $httpClient->sendAsyncRequest($request)
                ->then(
                    function (ResponseInterface $response) use ($doctrine, $character, $httpClient, &$films) {
                        $SWApiFilms = json_decode($response->getBody());
                        foreach ($SWApiFilms->films as $SWFilmApi){

                            $cacheApi = $doctrine->getRepository(CacheApi::class)->findOneBy([
                                'path' => $SWFilmApi
                            ]);

                            if(!$cacheApi){
                                $charRequest = $httpClient->createRequest('GET', $SWFilmApi);
                                //----------------
                                $promise = $httpClient->sendAsyncRequest($charRequest)
                                    ->then(
                                        function (ResponseInterface $response) use ($doctrine, $SWFilmApi, $character, $httpClient, &$films) {
                                            $SWApiFilm = json_decode($response->getBody());
                                            $entityManager = $doctrine->getManager();
                                            $film = $doctrine->getRepository(Film::class)->findOneBy([
                                                'apiUrl' => $SWApiFilm->url
                                            ]);
                                            if(!$film){
                                                $film = new Film();
                                                $film->setTitle($SWApiFilm->title);
                                                $film->setDirector($SWApiFilm->director);
                                                $film->setReleaseDate(\DateTime::createFromFormat('Y-m-d', $SWApiFilm->release_date));
                                                $film->setApiUrl($SWApiFilm->url);
                                                $film->addCharacter($character);
                                                $entityManager->persist($film);
                                                $entityManager->flush();
                                            }
                                            array_push($films, $film);
                                            $cacheApi = new CacheApi();
                                            $cacheApi->setPath($SWFilmApi);
                                            $entityManager->persist($cacheApi);
                                            $entityManager->flush();
                                            return $response;
                                        },
                                        function (\Throwable $exception) {
                                            throw $exception;
                                        }
                                    );
                                $promise->wait();
                                //---------------
                            }else{
                                array_push($films, $doctrine->getRepository(Film::class)->findOneBy([
                                    'apiUrl' => $SWFilmApi
                                ]));
                            }
                        }
                        return $response;
                    },
                    function (\Throwable $exception) {
                        throw $exception;
                    }
                );
            $promise->wait();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $this->render('character/single.html.twig', compact('character', 'films'));
    }

    /**
     * @Route(name="characters", path="/characters")
     * @Route(name="characters_page", path="/characters/{page}")
     */
    public function characters(ManagerRegistry $doctrine, $page = 1): Response{
        $SWCharacterApiPath = 'https://swapi.dev/api/people/?page=' . $page;
        $cacheApi = $doctrine->getRepository(CacheApi::class)->findOneBy([
            'path' => $SWCharacterApiPath
        ]);
        $characters = [];

        if(!$cacheApi){
            $httpClient = new HttplugClient();
            $request = $httpClient->createRequest('GET', $SWCharacterApiPath);
            try {
                $promise = $httpClient->sendAsyncRequest($request)
                    ->then(
                        function (ResponseInterface $response) use ($doctrine, $SWCharacterApiPath, &$characters) {
                            $SWApiCharacters = json_decode($response->getBody());
                            $entityManager = $doctrine->getManager();
                            foreach ($SWApiCharacters->results as $SWCharacter) {
                                $character = $doctrine->getRepository(Character::class)->findOneBy([
                                    'apiUrl' => $SWCharacter->url
                                ]);
                                if(!$character){
                                    $character = new Character();
                                    $character->setName($SWCharacter->name);
                                    $character->setGender($SWCharacter->gender);
                                    $character->setSpecie('no-one');
                                    $character->setApiUrl($SWCharacter->url);
                                    $entityManager->persist($character);
                                    $entityManager->flush();
                                }
                                array_push($characters, $character);
                            }
                            $cacheApi = new CacheApi();
                            $cacheApi->setPath($SWCharacterApiPath);
                            $entityManager->persist($cacheApi);
                            $entityManager->flush();

                            //return $response;
                        },
                        function (\Throwable $exception) {
                            throw $exception;
                        }
                    );
                $promise->wait();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }else{
            $offset = ($page - 1) * 10;
            $characters = $doctrine->getRepository(Character::class)->findBy([], [], 10, $offset);
        }
        return $this->render('character/index.html.twig', compact('characters', 'page'));
    }

    /**
     * @Route(name="films", path="/films")
     */
    public function films(ManagerRegistry $doctrine): Response{
        $SWFilmApiPath = 'https://swapi.dev/api/films/';
        $cacheApi = $doctrine->getRepository(CacheApi::class)->findOneBy([
            'path' => $SWFilmApiPath
        ]);
        $films = [];

        if(!$cacheApi){
            $httpClient = new HttplugClient();
            $request = $httpClient->createRequest('GET', $SWFilmApiPath);
            try {
                $promise = $httpClient->sendAsyncRequest($request)
                ->then(
                    function (ResponseInterface $response) use ($doctrine, $SWFilmApiPath, &$films) {
                        $SWApiFilms = json_decode($response->getBody());
                        $entityManager = $doctrine->getManager();
                        foreach ($SWApiFilms->results as $SWFilm) {
                            $film = $doctrine->getRepository(Film::class)->findOneBy([
                                'title' => $SWFilm->title
                            ]);
                            if(!$film){
                                $film = new Film();
                                $film->setTitle($SWFilm->title);
                                $film->setDirector($SWFilm->director);
                                $film->setReleaseDate(\DateTime::createFromFormat('Y-m-d', $SWFilm->release_date));
                                $film->setApiUrl($SWFilm->url);
                                $entityManager->persist($film);
                                $entityManager->flush();
                            }
                            array_push($films, $film);
                        }
                        $cacheApi = new CacheApi();
                        $cacheApi->setPath($SWFilmApiPath);
                        $entityManager->persist($cacheApi);
                        $entityManager->flush();

                        return $response;
                    },
                    function (\Throwable $exception) {
                        throw $exception;
                    }
                );
                $promise->wait();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }else{
            $films = $doctrine->getRepository(Film::class)->findAll();
        }

        return $this->render('film/index.html.twig', compact('films'));
    }


    /**
     * @Route(name="film_single", path="film/{id}")
     */
    public function film(ManagerRegistry $doctrine, $id): Response{
        $film = $doctrine->getRepository(Film::class)->findCharacters($id);
        $SWFilmApiPath = $film->getApiUrl();
        $characters = [];
        try {
            $httpClient = new HttplugClient();
            $request = $httpClient->createRequest('GET', $SWFilmApiPath);
            $promise = $httpClient->sendAsyncRequest($request)
                ->then(
                    function (ResponseInterface $response) use ($doctrine, $film, $httpClient, &$characters) {
                        $SWApiFilms = json_decode($response->getBody());
                        foreach ($SWApiFilms->characters as $SWCharacterApi){
                            $cacheApi = $doctrine->getRepository(CacheApi::class)->findOneBy([
                                'path' => $SWCharacterApi
                            ]);
                            if(!$cacheApi){
                                $charRequest = $httpClient->createRequest('GET', $SWCharacterApi);
                                //----------------
                                $promise = $httpClient->sendAsyncRequest($charRequest)
                                    ->then(
                                        function (ResponseInterface $response) use ($doctrine, $SWCharacterApi, $film, $httpClient, &$characters) {
                                            $SWApiChar = json_decode($response->getBody());
                                            $entityManager = $doctrine->getManager();
                                            $character = $doctrine->getRepository(Character::class)->findOneBy([
                                                'apiUrl' => $SWApiChar->url
                                            ]);
                                            if(!$character){
                                                $character = new Character();
                                                $character->setName($SWApiChar->name);
                                                $character->setGender($SWApiChar->gender);
                                                $character->setSpecie('no-one');
                                                $character->addFilm($film);
                                                $character->setApiUrl($SWApiChar->url);
                                                $entityManager->persist($character);
                                                $entityManager->flush();
                                            }
                                            array_push($characters, $character);
                                            $cacheApi = new CacheApi();
                                            $cacheApi->setPath($SWCharacterApi);
                                            $entityManager->persist($cacheApi);
                                            $entityManager->flush();
                                            return $response;
                                        },
                                        function (\Throwable $exception) {
                                            throw $exception;
                                        }
                                    );
                                $promise->wait();
                                //---------------
                            }else{
                                array_push($characters, $doctrine->getRepository(Character::class)->findOneBy([
                                    'apiUrl' => $SWCharacterApi
                                ]));
                            }
                        }
                        return $response;
                    },
                    function (\Throwable $exception) {
                        throw $exception;
                    }
                );
            $promise->wait();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $this->render('film/single.html.twig', compact('film', 'characters'));
    }
}