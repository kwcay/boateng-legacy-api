<?php

namespace App\Http\Controllers;

use App\Tracker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     *
     */
    private $tracker;

    /**
     * @param  Illuminate\Http\Request  $request
     * @param  App\Tracker              $tracker
     * @return void
     */
    public function __construct(Request $request, Tracker $tracker)
    {
        // Determine internal name from class name.
        if (! isset($this->name)) {
            $namespace  = explode('\\', get_class($this));
            $this->name = strtolower(substr(array_pop($namespace), 0, -10));
        }

        $this->request  = $request;
        $this->tracker  = $tracker;
    }
}
