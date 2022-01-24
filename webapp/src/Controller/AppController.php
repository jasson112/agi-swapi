<?php
namespace App\Controller;

use App\Entity\CacheApi;
use App\Entity\Character;
use App\Entity\Film;
use App\Repository\FilmRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AppController  extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/")
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
     * @Route(name="characters", path="/characters")
     */
    public function characters(ManagerRegistry $doctrine): Response{
        $SWCharacterApiPath = 'https://swapi.dev/api/characters/';
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
                        function (ResponseInterface $response) use ($doctrine, $SWCharacterApiPath, $films) {
                            $SWApiCharacters = json_decode($response->getBody());
                            $entityManager = $doctrine->getManager();
                            foreach ($SWApiCharacters->results as $SWCharacter) {
                                $character = $doctrine->getRepository(Character::class)->findOneBy([
                                    'name' => $SWCharacter->name
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

        return $this->render('character/index.html.twig', compact('characters'));
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
                    function (ResponseInterface $response) use ($doctrine, $SWFilmApiPath, $films) {
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
        $httpClient = new HttplugClient();
        $request = $httpClient->createRequest('GET', $SWFilmApiPath);
        $characters = $film->getCharacters()->toArray();
        try {
            $promise = $httpClient->sendAsyncRequest($request)
                ->then(
                    function (ResponseInterface $response) use ($doctrine, $film, $httpClient, $characters) {
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
                                        function (ResponseInterface $response) use ($doctrine, $SWCharacterApi, $film, $httpClient, $characters) {
                                            $SWApiChar = json_decode($response->getBody());
                                            $entityManager = $doctrine->getManager();
                                            $character = $doctrine->getRepository(Character::class)->findOneBy([
                                                'name' => $SWApiChar->name
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