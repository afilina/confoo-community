<?php
namespace ApiBundle\Entity;

/**
 * Automatically try to call a getProperty or setProperty.
 * If a method doesn't exist, it will try to find a property with that name.
 * This saves a lot of getter/setter method creation, since models aren't supposed to have
 * inaccessible properties.
 */
trait AccessorTrait
{
    public function __get($property)
    {
        $getter = 'get'.preg_replace_callback('/(?:^|_)(.?)/', function($matches) {
            return strtoupper($matches[1]);
        }, $property);

        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }

        if (property_exists($this, $property)) {
            return $this->$property;
        }

        throw new \Exception("Property {$property} does not exist.");
    }

    public function __isset($property)
    {
        $getter = 'get'.preg_replace_callback('/(?:^|_)(.?)/', function($matches) {
            return strtoupper($matches[1]);
        }, $property);

        if (method_exists($this, $getter)) {
            return true;
        }

        if (property_exists($this, $property)) {
            return true;
        }

        return false;
    }

    public function __set($property, $value)
    {
        $setter = 'set'.preg_replace_callback('/(?:^|_)(.?)/', function($matches) {
            return strtoupper($matches[1]);
        }, $property);

        if (method_exists($this, $setter)) {
            return $this->{$setter}($value);
        }

        if (property_exists($this, $property)) {
            return $this->$property = $value;
        }
        throw new \Exception("Property {$property} does not exist.");
    }

    public function setId($id)
    {
        if ($id === '0' || !is_numeric($id)) {
            return;
        }
        $this->id = (int)$id;
    }

    public function toArray($object = null, $path = '')
    {
        $array = [];
        if ($object == null) {
            $object = $this;
        }
        $path .= ';'.get_class($this);

        if ($object instanceof \Doctrine\Common\Proxy\Proxy) {
            $array['id'] = $object->id;
            return $array;
        }

        foreach ($object as $property => $value) {
            if ($value instanceof \ArrayAccess) {
                $value = $this->toArray($value, $path);
            }
            else if (is_object($value) && method_exists($value, 'toArray')) {
                if (strpos($path, get_class($value))) {
                    continue;
                }
                $value = $value->toArray(null, $path);
            }
            if ($object instanceof \ArrayAccess) {
                // Avoids indexing issues when removing many-to-many associations.
                $array[] = $value;
                continue;
            }
            $array[$property] = $value;
        }
        return $array;
    }

    public function fromArray($array)
    {
        foreach ($array as $property => $value) {
            // TODO: implement nesting
            $this->$property = $value;
        }
    }
}