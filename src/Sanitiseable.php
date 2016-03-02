<?php

namespace Dmlogic\Traits;

use Purifier;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait Sanitiseable {

    protected $raw;
    protected $allowed;
    protected $return = [];

    /**
     * Set the values we need
     *
     * @param array $data
     * @param array $allowed
     */
    public function setData($data,$allowed)
    {
        $this->allowed = $allowed;
        $this->raw = (array) $data;
    }

    /**
     * Factory helper
     *
     * @param  array $data
     * @return array
     */
    public static function cleanData($data,$allowed)
    {
        $cls = new static;
        $cls->setData($data,$allowed);
        return $cls->clean();
    }

    /**
     * Public interface if we don't want to use the factory
     *
     * @return array
     */
    public function clean()
    {
        $this->performSanitise();
        return $this->return;
    }

    /**
     * Does all the work
     *
     * @return void
     */
    public function performSanitise()
    {
        foreach($this->allowed as $field) {

            // Only check fillable fields
            if(!array_key_exists($field, $this->raw)) {
                continue;
            }

            $value = $this->raw[$field];

            // Plain strings
            if(in_array($field, $this->getFields('plainString'))) {
                $value = $this->sanitiseString($value);
            }
            // Booleans
            if(in_array($field, $this->getFields('boolean'))) {
                $value = $this->sanitiseBoolean($value);
            }
            // Integers
            if(in_array($field, $this->getFields('integer'))) {
                $value = $this->sanitiseInteger($value);
            }
            // URLs
            if(in_array($field, $this->getFields('url'))) {
                $value = $this->sanitiseUrl($value);
            }
            // Things that should be null if empty
            if(in_array($field, $this->getFields('notBlank')) && empty($value)) {
                $value = null;
            }
            // HTML
            if(in_array($field, $this->getFields('html'))) {
                $value = $this->sanitiseHTML($value);
            }
            if(in_array($field, $this->getFields('upload'))) {
                $value = $this->sanitiseUploads($value);
            }
            // Custom modifiers
            $method = 'sanitiseField'.studly_case($field);
            if(method_exists($this, $method)) {
                $value = $this->$method($value);
            }

            $this->return[$field] = $value;
        }

    }

    public function sanitiseEmail($value) {
        return trim(filter_var($value,FILTER_SANITIZE_EMAIL));
    }

    public function sanitiseString($value)
    {
        return trim(filter_var($value,FILTER_SANITIZE_STRING));
    }

    public function sanitiseBoolean($value)
    {
        return (bool) $value;
    }

    public function sanitiseInteger($value)
    {
        return (int) $value;
    }

    public function sanitiseUrl($value)
    {
        $value = filter_var($value,FILTER_SANITIZE_STRING);
        return trim(filter_var($value,FILTER_SANITIZE_URL));
    }

    public function sanitiseWithin($value,$allowed = [])
    {
        if(in_array($value, $allowed)) {
            return $value;
        }

        return '';
    }

    public function sanitiseHTML($value)
    {
        return Purifier::clean($value);
    }

    public function sanitiseUploads($value)
    {
        if(!is_array($value)) {
            return $this->sanitiseUpload($value);
        }

        $ret = [];
        foreach ($value as $doc) {
            if($clean = $this->sanitiseUpload($doc)) {
                $ret[] = $clean;
            }
        }

        return $ret;
    }

    public function sanitiseUpload($value)
    {
        if(!$value instanceof UploadedFile) {
            return null;
        }
        return $value;
    }

    protected function getFields($key)
    {
        $property = $key.'Fields';
        if(!property_exists($this, $property)) {
            return [];
        }

        return $this->$property;
    }
}