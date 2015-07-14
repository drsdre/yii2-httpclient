<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\httpclient;

use yii\base\ErrorHandler;
use yii\base\InvalidConfigException;
use yii\base\Object;
use yii\web\HeaderCollection;
use Yii;

/**
 * Message represents a base HTTP message.
 *
 * @property HeaderCollection|array $headers message headers list.
 * @property string $content message raw content.
 * @property array $data message content data.
 * @property string $format message content format.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
class Message extends Object implements MessageInterface
{
    /**
     * @var array the formatters for converting data into the content of the specified [[format]].
     * The array keys are the format names, and the array values are the corresponding configurations
     * for creating the formatter objects.
     */
    public $formatters = [];
    /**
     * @var array the parsers for converting content of the specified [[format]] into the data.
     * The array keys are the format names, and the array values are the corresponding configurations
     * for creating the parser objects.
     */
    public $parsers = [];
    /**
     * @var HeaderCollection headers.
     */
    private $_headers;
    /**
     * @var string|null raw content
     */
    private $_content;
    /**
     * @var array content data
     */
    private $_data;
    /**
     * @var string content format name
     */
    private $_format;


    /**
     * Sets the HTTP headers associated with HTTP message.
     * @param array|HeaderCollection $headers headers collection or headers list in format: [headerName => headerValue]
     * @return $this self reference.
     */
    public function setHeaders($headers)
    {
        $this->_headers = $headers;
        return $this;
    }

    /**
     * Returns the header collection.
     * The header collection contains the HTTP headers associated with HTTP message.
     * @return HeaderCollection the header collection
     */
    public function getHeaders()
    {
        if (!is_object($this->_headers)) {
            $headerCollection = new HeaderCollection();
            if (is_array($this->_headers)) {
                foreach ($this->_headers as $name => $value) {
                    $headerCollection->add($name, $value);
                }
            }
            $this->_headers = $headerCollection;
        }
        return $this->_headers;
    }

    /**
     * Adds HTTP headers to the headers collection.
     * @param array $headers headers list in format: [headerName => headerValue]
     * @return $this self reference.
     */
    public function addHeaders(array $headers)
    {
        $headerCollection = $this->getHeaders();
        foreach ($headers as $name => $value) {
            $headerCollection->add($name, $value);
        }
        return $this;
    }

    /**
     * Sets the HTTP message raw content.
     * @param string $content raw content.
     * @return $this self reference.
     */
    public function setContent($content)
    {
        $this->_content = $content;
        return $this;
    }

    /**
     * Returns HTTP message raw content.
     * @return string raw body.
     */
    public function getContent()
    {
        if ($this->_content === null && !empty($this->_data)) {
            $this->_content = $this->createFormatter()->format($this);
        }
        return $this->_content;
    }

    /**
     * Sets the data fields, which composes message content.
     * @param array $data content data fields.
     * @return $this self reference.
     */
    public function setData(array $data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * Returns the data fields, parsed from raw content.
     * @return array content data fields.
     */
    public function getData()
    {
        if ($this->_data === null && !empty($this->_content)) {
            $this->_data = $this->createParser()->parse($this);
        }
        return $this->_data;
    }

    /**
     * Sets body format.
     * @param string $format body format name.
     * @return $this self reference.
     */
    public function setFormat($format)
    {
        $this->_format = $format;
        return $this;
    }

    /**
     * Returns body format.
     * @return string body format name.
     */
    public function getFormat()
    {
        if ($this->_format === null) {
            $this->_format = $this->defaultFormat();
        }
        return $this->_format;
    }

    /**
     * Returns default format name.
     * @return string default format name.
     */
    protected function defaultFormat()
    {
        return MessageInterface::FORMAT_URLENCODED;
    }

    /**
     * Returns string representation of this HTTP message.
     * @return string the string representation of this HTTP message.
     */
    public function toString()
    {
        $headerParts = [];
        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headerParts[] = "$name : $value";
            }
        }
        return implode("\n", $headerParts) . "\n\n" . $this->getContent();
    }

    /**
     * PHP magic method that returns the string representation of this object.
     * @return string the string representation of this object.
     */
    public function __toString()
    {
        // __toString cannot throw exception
        // use trigger_error to bypass this limitation
        try {
            return $this->toString();
        } catch (\Exception $e) {
            ErrorHandler::convertExceptionToError($e);
            return '';
        }
    }

    /**
     * @return FormatterInterface message formatter instance.
     * @throws InvalidConfigException on unrecognized format.
     */
    private function createFormatter()
    {
        $format = $this->getFormat();
        if (array_key_exists($format, $this->formatters)) {
            return Yii::createObject($this->formatters[$format]);
        } else {
            $defaultFormatters = [
                MessageInterface::FORMAT_URLENCODED => 'yii\httpclient\FormatterUrlEncoded',
                MessageInterface::FORMAT_JSON => 'yii\httpclient\FormatterJson',
                //DocumentInterface::FORMAT_XML => 'yii\httpclient\FormatterXml',
            ];
            if (array_key_exists($format, $defaultFormatters)) {
                return Yii::createObject($defaultFormatters[$format]);
            }
            throw new InvalidConfigException("Unrecognized format '{$format}'");
        }
    }

    /**
     * @return ParserInterface message parser instance.
     * @throws InvalidConfigException on unrecognized format.
     */
    private function createParser()
    {
        $format = $this->getFormat();
        if (array_key_exists($format, $this->parsers)) {
            return Yii::createObject($this->parsers[$format]);
        } else {
            $defaultParsers = [
                MessageInterface::FORMAT_URLENCODED => 'yii\httpclient\ParserUrlEncoded',
                MessageInterface::FORMAT_JSON => 'yii\httpclient\ParserJson',
                MessageInterface::FORMAT_XML => 'yii\httpclient\ParserXml',
            ];
            if (array_key_exists($format, $defaultParsers)) {
                return Yii::createObject($defaultParsers[$format]);
            }
            throw new InvalidConfigException("Unrecognized format '{$format}'");
        }
    }
}