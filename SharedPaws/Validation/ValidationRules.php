<?php

namespace SharedPaws\Validation;

class ValidationRules
{
    private array $list = /* @jsobject */ [];

    private function __construct(private $target) {}

    public static function rules($target)
    {
        return new ValidationRules($target);
    }

    public function required($prop, $error = null)
    {
        $this->ensure($prop);
        $this->list[$prop]['required'] = fn() => !$this->target->{$prop} ? ($error ?? "$prop is required") : true;
        return $this;
    }

    public function isEmail(string $value)
    {
        return strpos($value, '@') !== false;
    }

    public function validateEmail($email): bool
    {
        $parts = explode('@', $email, 2);
        return !!(explode('.', $parts[1] ?? '')[1] ?? false);
    }

    public function validateHttpUrl($url): bool
    {
        // (http(s)://)(domain)/(the rest)
        $valid = true;
        $parts = explode('/', $url, 4);
        $total = count($parts);
        $domain = $parts[0];
        if ($total > 2 && ($domain === 'http:' || $domain === 'https:') && !$parts[1]) {
            $domain = $parts[2];
        }
        // validate domain
        $domainParts = explode('.', $domain);
        $domainTotal = count($domainParts);
        $valid = $domainTotal > 1;
        $valid = $valid && mb_strlen($domainParts[$domainTotal - 1]) > 1 && ctype_alnum($domainParts[$domainTotal - 1]);
        if ($valid) {
            foreach ($domainParts as $domainSection) {
                $valid = $valid && $domainSection && ctype_alnum(str_replace('-', '', $domainSection));
            }
        }
        return $valid;
    }

    public function validatePhone($phone)
    {
        $phone = trim(str_replace([' ', '-'], '', $phone));
        if (strpos($phone, '+') === 0) {
            $phone = substr($phone, 1);
        }
        return ctype_digit($phone);
    }

    public function requiredAny($prop, $prop2, $error = null)
    {
        $this->ensure($prop);
        $this->list[$prop]['requiredAny'] = fn() => $this->target->{$prop} || $this->target->{$prop2} ? true : ($error ?? "$prop or $prop2 is required.");
        return $this;
    }

    public function email($prop, $emailError = null)
    {
        $this->ensure($prop);
        $this->list[$prop]['email'] = function () use ($prop, $emailError) {
            if ($this->target->{$prop}) {
                return $this->validateEmail($this->target->{$prop}) ? true : ($emailError ?? 'Wrong email format.');
            }
            // no value
            return true;
        };
        return $this;
    }

    public function httpUrl($prop, $errorMessage = null)
    {
        $this->ensure($prop);
        $this->list[$prop]['httpUrl'] = function () use ($prop, $errorMessage) {
            if ($this->target->{$prop}) {
                return $this->validateHttpUrl($this->target->{$prop}) ? true : ($errorMessage ?? 'Wrong URL format.');
            }
            // no value
            return true;
        };
        return $this;
    }

    public function phone($prop, $phoneError = null)
    {
        $this->ensure($prop);
        $this->list[$prop]['phone'] = function () use ($prop, $phoneError) {
            if ($this->target->{$prop}) {
                return $this->validatePhone($this->target->{$prop}) ? true : ($phoneError ?? 'Wrong phone number');
            }
            return true;
        };
        return $this;
    }

    public function emailOrPhone($prop, $emailError = null, $phoneError = null)
    {
        $this->ensure($prop);
        $this->list[$prop]['emailOrPhone'] = function () use ($prop, $emailError, $phoneError) {
            $emailOrPhone = $this->target->{$prop};
            if ($emailOrPhone) {
                if ($this->isEmail($emailOrPhone)) {
                    return $this->validateEmail($emailOrPhone) ? true : ($emailError ?? 'Wrong email format');
                } else {
                    return $this->validatePhone($emailOrPhone) ? true : ($phoneError ?? 'Wrong phone number');
                }
            }
            return true;
        };
        return $this;
    }

    public function requiredAtLeast($prop, $prop2)
    {
        $this->ensure($prop);
        $this->list[$prop]['required'] = fn() => (!!$this->target->{$prop} || !!$this->target->{$prop2}) ? true : "$prop or $prop2 is required";
        return $this;
    }

    public function maxLength($prop, $maxLength)
    {
        $this->ensure($prop);
        $this->list[$prop]['maxlength'] = fn() => (!$this->target->{$prop} || mb_strlen($this->target->{$prop}, 'UTF-8') <= $maxLength) ? true : "$prop should not exceed $maxLength characters in length";
        return $this;
    }

    public function minLength($prop, $minLength)
    {
        $this->ensure($prop);
        $this->list[$prop]['minlength'] = fn() => (!$this->target->{$prop} || mb_strlen($this->target->{$prop}, 'UTF-8') >= $minLength) ? true : "$prop must be at least $minLength characters long";
        return $this;
    }

    public function match($prop, $matchToProp, $error = null)
    {
        $this->ensure($prop);
        $this->list[$prop]['match'] = fn() => ($this->target->{$matchToProp} ?? '') === ($this->target->{$prop} ?? '') ? true : ($error ?? "$prop should match $matchToProp");
        return $this;
    }

    private function ensure($prop)
    {
        if (!isset($this->list[$prop])) {
            $this->list[$prop] = /* @jsobject */ [];
        }
    }

    public function toList()
    {
        return $this->list;
    }
}
