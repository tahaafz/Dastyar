<?php

namespace App\Telegram\Core;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class Context
{
    /** @var array<string,class-string<State>> */
    protected array $map;
    protected Model $record;

    public function __construct(Model $record, array $stateMap)
    {
        $this->record = $record;
        $this->map = $stateMap;
    }

    public function record(): Model { return $this->record; }

    public function getState(): State
    {
        $key = $this->record->tg_current_state;
        if (!$key) throw new RuntimeException('No tg_current_state on record');

        $class = $this->map[$key] ?? (class_exists($key) ? $key : null);
        if (!$class) throw new RuntimeException("State '{$key}' not registered");

        $state = app($class);
        $state->setParent($this);
        return $state;
    }

    public function transitionTo(string $classOrKey): void
    {
        $key = array_search($classOrKey, $this->map, true);
        $stateKey = $key !== false ? $key : $classOrKey;

        $this->record->tg_current_state = $stateKey;
        $this->record->save();

        $this->getState()->onEnter();
    }
}
