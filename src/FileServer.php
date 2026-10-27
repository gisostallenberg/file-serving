<?php

namespace GisoStallenberg\FileServing;

use DateTime;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;

class FileServer
{
    /**
     * The extensions that should be served, empty array means all
     *
     * @var array
     */
    private $allowedExtensions = array();
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
    public function __construct($from, $to, ?Request $request = null)
    {
        $this->from = (array) $from;
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
    public static function create($from, $to, ?Request $request = null)
    {
        return new static($from, $to, $request);
    }

    /**
     * Sets the allowed extensions
     *
     * @param array $allowedExtensions
     * @return FileServer
     */
    public function filterExtensions(array $allowedExtensions)
    {
        $this->allowedExtensions = $allowedExtensions;

        return $this;
    }

    /**
     * Creates the response object.
     *
     * @return Response
     */
    public function getResponse()
    {
        $requestPath = parse_url(urldecode($this->request->server->get('REQUEST_URI') ?? ''), PHP_URL_PATH);

        if (!is_string($requestPath) || !str_starts_with($requestPath, $this->to)) {
            return $this->getNotFoundResponse();
        }

        $relativePath = substr($requestPath, strlen($this->to));

        foreach ($this->from as $from) {
            $filePath = $from.$relativePath;

            if (!$this->isAllowedExtension(pathinfo($filePath, PATHINFO_EXTENSION))) {
                continue;
            }

            // Resolve symlinks and .. segments, then refuse anything that escapes the base directory.
            $realPath = realpath($filePath);
            $basePath = realpath($from);
            if ($realPath === false || $basePath === false || !str_starts_with($realPath, $basePath.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $response = new BinaryFileResponse($realPath);
            $response->setAutoLastModified();
            $response->setExpires(new DateTime('+1 week'));
            $response->headers->set('Content-Type', $this->getContentType($realPath));
            $response->isNotModified($this->request);

            return $response;
        }

        return $this->getNotFoundResponse();
    }

    /**
     * Test to see if the extension is allowed
     *
     * @param string $extension
     * @return boolean
     */
    private function isAllowedExtension($extension)
    {
        if (empty($this->allowedExtensions)) {
            return true;
        }
        return in_array(strtolower($extension), array_map('strtolower', $this->allowedExtensions), true);
    }

    /**
     * Determines the content type for a file.
     *
     * Uses a small built-in map first and falls back to the symfony/mime
     * component when it is installed, so the library works without it.
     *
     * @param string $path
     * @return string
     */
    private function getContentType($path)
    {
        $map = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'mjs' => 'application/javascript',
            'json' => 'application/json',
            'map' => 'application/json',
            'xml' => 'application/xml',
            'html' => 'text/html',
            'htm' => 'text/html',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            'eot' => 'application/vnd.ms-fontobject',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
        ];

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (isset($map[$extension])) {
            return $map[$extension];
        }

        if (class_exists(MimeTypes::class)) {
            return MimeTypes::getDefault()->guessMimeType($path) ?: 'application/octet-stream';
        }

        return 'application/octet-stream';
    }

    /**
     * Gives the NOT FOUND Response object
     *
     * @return Response
     */
    private function getNotFoundResponse()
    {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    /**
     * Serves the file.
     */
    public function serve()
    {
        $this->getResponse()->send();
    }
}
