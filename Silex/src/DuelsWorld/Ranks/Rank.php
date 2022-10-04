<?php

namespace DuelsWorld\Ranks;

class Rank{

    /** @var string */
    public string $name;
    /** @var array */
    public array $permissions;
    /** @var string */
    public string $chatFormat;
    /** @var bool */
    public bool $default;
    /** @var string */
    public string $nameTag;
    /** @var int */
    public int $tpk = 1;

    public function __construct(string $name, string $chatFormat, array $permissions = [], string $nameTag = "{display_name}", bool $isDefault = false, int $tpk = 1){
        $this->name = $name;
        $this->nameTag = $nameTag;
        $this->permissions = $permissions;
        $this->chatFormat = $chatFormat;
        $this->default = $isDefault;
        $this->tpk = $tpk;
    }
}