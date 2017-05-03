<?php

namespace App\Validators;
use App\Services\GrazerRedis\GrazerRedisService;
use Illuminate\Support\Facades\Log;

class UserValidator
{

    public function __construct(GrazerRedisService $grazerRedisService)
    {
        $this->datastore = $grazerRedisService;
    }

    /**
     * Unique email check - return whether email is unique.
     *
     * @return bool
     */
    public function isEmailUnique($attribute, $value, $parameters, $validator) : bool
    {   
        Log::debug("email exists $value");
        return !$this->datastore->emailExists($value);
    }

}