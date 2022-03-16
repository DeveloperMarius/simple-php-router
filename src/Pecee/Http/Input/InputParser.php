<?php

namespace Pecee\Http\Input;

class InputParser
{

    /**
     * @var InputItem $inputItem
     */
    private InputItem $inputItem;

    /**
     * @param InputItem $inputItem
     */
    public function __construct(InputItem $inputItem)
    {
        $this->inputItem = $inputItem;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->getInputItem()->getValue();
    }

    /**
     * @param callable|string $setting
     * @return self
     */
    public function parseFromSetting(callable|string $setting): self
    {
        if (is_callable($setting)) {
            $this->setValue($setting($this->getInputItem()));
        } else {
            if ($this->getValue() === null)
                return $this;
            switch ($setting) {
                case 'string':
                    $this->toString();
                    break;
                case 'integer':
                case 'int':
                    $this->toInteger();
                    break;
                case 'float':
                    $this->toFloat();
                    break;
                case 'boolean':
                case 'bool':
                    $this->toBoolean();
                    break;
                case 'email':
                    $this->sanitizeEmailAddress();
                    break;
                case 'ip':
                    $this->toIp();
                    break;
                case 'ipv4':
                    $this->toIp('ipv4');
                    break;
                case 'ipv6':
                    $this->toIp('ipv6');
                    break;
            }
        }
        return $this;
    }

    /**
     * @param mixed $value
     * @return self
     */
    private function setValue(mixed $value): self
    {
        $this->getInputItem()->setValue($value);
        return $this;
    }

    /**
     * @return InputItem
     */
    public function getInputItem(): InputItem
    {
        return $this->inputItem;
    }

    /* https://www.php.net/manual/de/filter.filters.validate.php */

    /**
     * @return bool|null
     */
    public function toBoolean(): ?bool
    {
        $this->setValue(filter_var($this->getValue(), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
        return $this->getValue();
    }

    /**
     * @return string
     */
    public function toDomain(): string
    {
        $this->setValue(filter_var($this->getValue(), FILTER_VALIDATE_DOMAIN, FILTER_NULL_ON_FAILURE));
        return $this->getValue();
    }

    /**
     * @return string
     */
    public function toEmail(): string
    {
        $this->setValue(filter_var($this->getValue(), FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE));
        return $this->getValue();
    }

    /**
     * @return float|null
     */
    public function toFloat(): ?float
    {
        $this->setValue(filter_var($this->getValue(), FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE));
        return $this->getValue();
    }

    /**
     * @return int|null
     */
    public function toInteger(): ?int
    {
        $this->setValue(filter_var($this->getValue(), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE));
        return $this->getValue();
    }

    /**
     * @param string|null $type ipv4, ipv6 or both
     * @return string|null
     */
    public function toIp(?string $type = null): ?string
    {
        switch ($type) {
            case 'ipv4':
                $this->setValue(filter_var($this->getValue(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_NULL_ON_FAILURE));
                break;
            case 'ipv6':
                $this->setValue(filter_var($this->getValue(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_NULL_ON_FAILURE));
                break;
            default:
                $this->setValue(filter_var($this->getValue(), FILTER_VALIDATE_IP, FILTER_NULL_ON_FAILURE));
                break;
        }
        return $this->getValue();
    }

    /* Other types */

    /**
     * @return string
     */
    public function toString(): string
    {
        $this->setValue(strval($this->getValue()));
        return $this->getValue();
    }

    /* Other */

    /**
     * @param array $allowed_tags
     * @return self
     */
    public function stripTags(array $allowed_tags = array()): self
    {
        return $this->setValue(strip_tags($this->getValue(), $allowed_tags));
    }

    /**
     * @return self
     */
    public function stripTags2(): self
    {
        return $this->setValue(filter_var($this->getValue(), FILTER_SANITIZE_STRING));
    }

    /**
     * @return self
     */
    public function htmlSpecialChars(): self
    {
        return $this->setValue(htmlspecialchars($this->getValue(), ENT_QUOTES | ENT_HTML5));
    }

    /**
     * Best practise with Inputs from Users
     *
     * @return self
     */
    public function sanitize(): self
    {
        return $this->htmlSpecialChars();
    }

    /**
     * @return self
     */
    public function sanitizeEmailAddress(): self
    {
        $this->toLower()->trim()->toEmail();
        return $this;
    }

    /**
     * @return self
     */
    public function urlEncode(): self
    {
        $this->setValue(urlencode($this->getValue()));
        return $this;
    }

    /**
     * @return self
     */
    public function base64Encode(): self
    {
        $this->setValue(base64_encode($this->getValue()));
        return $this;
    }

    /**
     * @return self
     */
    public function toLower(): self
    {
        $this->setValue(strtolower($this->getValue()));
        return $this;
    }

    /**
     * @return self
     */
    public function toUpper(): self
    {
        $this->setValue(strtoupper($this->getValue()));
        return $this;
    }

    /**
     * @return self
     */
    public function trim(): self
    {
        $this->setValue(trim($this->getValue()));
        return $this;
    }

    /**
     * Credits: https://stackoverflow.com/questions/2791998/convert-string-with-dashes-to-camelcase#answer-2792045
     * @param string $separator
     * @param bool $capitalizeFirstCharacter
     * @return self
     */
    function toCamelCase(string $separator = '_', bool $capitalizeFirstCharacter = false): self
    {
        $value = str_replace('-', '', ucwords($this->getValue(), $separator));

        if (!$capitalizeFirstCharacter) {
            $value = lcfirst($value);
        }
        $this->setValue($value);

        return $this;
    }

    /**
     * @param string $separator
     * @return self
     */
    function fromCamelCase(string $separator = '_'): self
    {
        $value = lcfirst($this->getValue());

        $this->setValue(preg_replace_callback('/[A-Z]/', function ($value) {
            return '_' . strtolower($value[0]);
        }, $value));

        return $this;
    }
}