<?php

namespace App\Http\Middleware;

use Closure;
use App\Tracker;
use League\OAuth2\Server\ResourceServer as OAuthServer;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class TrackRequests
{
    /**
     * @var App\Tracker
     */
    private $tracker;

    /**
     * @var League\OAuth2\Server\ResourceServer
     */
    private $server;

    /**
     * @var int
     */
    private $startTime;

    /**
     *
     */
    public function __construct(Tracker $tracker, OAuthServer $server)
    {
        $this->tracker      = $tracker;
        $this->server       = $server;
        $this->startTime    = microtime(true);
    }

    /**
     * Track every incoming request.
     *
     * @param  Illuminate\Http\Request  $request
     * @param  Closure                  $next
     * @return Illuminate\Http\Response
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Gather defaults to track every request
        $version    = substr($request->path(), 0, strpos($request->path(), '/'));
        $endpoint   = substr($request->path(), strpos($request->path(), '/'));
        $params     = $request->toArray();
        $clientId   = 0;
        $clientName = '';
        $scopes     = null;
        $userId     = (int) ($request->user() ? $request->user()->uniqueId : 0);
        $fingerprint    = null;

        // Try to retrieve route information
        // TODO: is it necessary to use the $request for most of these params?
        if ($request->route()) {
            $version    = $request->route()->getPrefix();
            $params     += $request->route()->parameters();
            $endpoint   = $request->route()->uri();
            $endpoint   = rtrim(str_replace($version, '', substr($endpoint, 0, strpos($endpoint, '{'))), '/');
            $fingerprint    = $request->fingerprint();
        }

        // Try to retrieve OAuth client information
        // TODO: is there a better way to do this?
        try {
            $validatedRequest = $this->server->validateAuthenticatedRequest(
                (new DiactorosFactory)->createRequest($request)
            );

            $scopes     = $validatedRequest->getAttribute('oauth_scopes');
            $clientId   = app('Obfuscator')->encode((int) $validatedRequest->getAttribute('oauth_client_id'));

            if ($client = \Laravel\Passport\Client::find($validatedRequest->getAttribute('oauth_client_id'))) {
                $clientName = $client->name.' ('.$clientId.')';
            }
        } catch (\Exception $e) {}

        $this->tracker->addEvent('request', [
            'method'        => $request->method(),
            'host'          => $request->root(),
            'version'       => $version,
            'endpoint'      => $endpoint,
            'input'         => $params,
            'fingerprint'   => $fingerprint,
            'ip'            => $request->ip(),
            'user-agent'    => $request->headers->get('user-agent'),
            'user-id'       => $userId,
            'client-id'     => $clientId,
            'client-name'   => $clientName,
            'oauth-scopes'  => $scopes,
            'time'          => microtime(true) - $this->startTime,
        ]);

        return $response;
    }

    public function terminate($request, $response)
    {
        // Store the session data
        $this->tracker->persist();
    }
}
