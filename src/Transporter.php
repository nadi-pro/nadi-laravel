<?php

namespace Nadi\Laravel;

use Nadi\Transporter\Contract;

class Transporter
{
    protected string $driver;

    protected Contract $transporter;

    public function __construct()
    {
        $this->driver = '\\Nadi\\Transporter\\'.ucfirst(config('nadi.driver'));

        if (! class_exists($this->driver)) {
            throw new \Exception("$this->driver did not exists");
        }

        if (! in_array(Contract::class, class_implements($this->driver))) {
            throw new \Exception("$this->driver did not implement the \Nadi\Transporter\Contract class.");
        }

        $this->transporter = (new $this->driver)
            ->configure(
                config('nadi.connections.'.config('nadi.driver'))
            );
    }

    public static function make()
    {
        return new self();
    }

    public function store(array $data)
    {
        return $this->transporter->store($data);
    }

    public function send()
    {
        return $this->transporter->send();
    }

    public function test()
    {
        return $this->transporter->test();
    }

    public function verify()
    {
        return $this->transporter->verify();
    }

    public function __destruct()
    {
        $this->transporter->send();
    }
}
