<?php

namespace Jundayw\MessagePackCodec\Contract;

interface Context
{
    public function parent(): Context;

}
