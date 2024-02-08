<?php

namespace App\Models\Back;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Jobs extends Model
{

    /**
     * @var string
     */
    protected $table = 'jobs';

    /**
     * @var array
     */
    protected $guarded = ['id'];

    public $timestamps = false;

    protected $identificator;
    protected $begin;
    protected $end;


    /**
     * @param string $type
     * @param string $target
     * @param string $payload
     * @param string $response
     *
     * @return int
     */
    public function start(string $type, string $target, string $payload = '', string $response = ''): int
    {
        $this->begin = microtime(true);

        $this->identificator = $this->newQuery()->insertGetId([
            'type' => $type,
            'target' => $target,
            'time' => '00:00',
            'success' => 0,
            'payload' => $payload,
            'response' => $response,
            'send_report' => 0,
            'created_at' => now()
        ]);

        return $this->identificator;
    }


    /**
     * @param int    $success
     * @param string $payload
     * @param string $response
     *
     * @return int
     */
    public function finish(int $success, string $payload, string $response): int
    {
        $this->end = microtime(true);
        $time = number_format(($this->end - $this->begin), 2, ',', '.');

        return $this->newQuery()->where('id', $this->identificator)->update([
            'time' => $time,
            'success' => $success,
            'payload' => $payload,
            'response' => $response,
            'send_report' => 0,
            'created_at' => now()
        ]);
    }


    /**
     * @param Request $request
     *
     * @return Builder
     */
    public static function filter(Request $request, string $type): Builder
    {
        $query = (new Jobs())->newQuery()->where('type', $type)->orderBy('created_at', 'desc');

        return $query;
    }


    /**
     * @param string $type
     * @param string $target
     * @param string $payload
     * @param string $response
     *
     * @return int
     */
    public static function error(string $type, string $target, string $payload = '', string $response = ''): int
    {
        $job = new Jobs();

        return $job->start($type, $target, $payload, $response);
    }



}
