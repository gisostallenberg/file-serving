<?php

namespace GisoStallenberg\FileServing;

use DateTime;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FileServer
{
    /**
     * The path to serve from.
     *
     * @var string
     */
    private $from;

    /**
     * The server path to serve.
     *
     * @var string
     */
    private $to;

    /**
     * The request object.
     *
     * @var Request
     */
    private $request;

    /**
     * Constructor.
     *
     * @param string  $from
     * @param string  $to
     * @param Request $request
     */
    public function __construct($from, $to, Request $request = null)
    {
        $this->from = $from;
        $this->to = $to;

        if (is_null($request)) {
            $request = Request::createFromGlobals();
        }
        $this->request = $request;
    }

    /**
     * Creates a new instance.
     *
     * @param string  $from
     * @param string  $to
     * @param Request $request
     *
     * @return \static
     */
    public static function create($from, $to, Request $request = null)
    {
        return new static($from, $to, $request);
    }

    /**
     * Creates the response object.
     *
     * @return Response
     */
    public function getResponse()
    {
        $requestPath = parse_url($this->request->server->get('REQUEST_URI'), PHP_URL_PATH);

        $filePath = $this->from.substr($requestPath, strlen($this->to));

        if (is_file($filePath)) {
            $file = new File($filePath);
            $response = Response::create()
                ->setExpires(new DateTime('+1 week'))
                ->setLastModified(DateTime::createFromFormat('U', $file->getMTime()));;

            if ($response->isNotModified($this->request)) {

                return $response;
            }

            $response->headers->set('Content-Type', $file->getMimeType() ?: 'application/octet-stream');
            return $response->setContent(file_get_contents($file->getPathname()));
        }

        return Response::create('', Response::HTTP_NOT_FOUND);
    }

    /**
     * Serves the file.
     */
    public function serve()
    {
        $this->getResponse()->send();
    }
}
