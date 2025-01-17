<?php
/**
 * @class RestServer
 *
 * A very simple REST server implementation
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper;

use Dotclear\Helper\Html\XmlTag;
use Exception;

class RestServer
{
    // Constants

    /**
     * Response format
     */
    public const XML_RESPONSE  = 0;
    public const JSON_RESPONSE = 1;

    public const DEFAULT_RESPONSE = self::XML_RESPONSE;

    /**
     * Response (XML)
     *
     * @var null|XmlTag
     */
    public $rsp;

    /**
     * Response (JSON)
     *
     * @var null|array
     */
    public $json;

    /**
     * Server's functions
     *
     * @var array
     */
    public array $functions = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->rsp = new XmlTag('rsp');
    }

    /**
     * Add Function
     *
     * This adds a new function to the server. <var>$callback</var> should be a valid PHP callback.
     *
     * Callback function takes two or three arguments:
     *  - supplemental parameter (if not null)
     *  - GET values
     *  - POST values
     *
     * @param string            $name        Function name
     * @param callable|array    $callback    Callback function
     */
    public function addFunction(string $name, $callback): void
    {
        if (is_callable($callback)) {
            $this->functions[$name] = $callback;
        }
    }

    /**
     * Call Function
     *
     * This method calls callback named <var>$name</var>.
     *
     * @param string    $name        Function name
     * @param array     $get         GET values
     * @param array     $post        POST values
     * @param mixed     $param       Supplemental parameter
     *
     * @return mixed
     */
    protected function callFunction(string $name, array $get, array $post, $param = null)
    {
        if (isset($this->functions[$name]) && is_callable($this->functions[$name])) {
            if ($param !== null) {
                return call_user_func($this->functions[$name], $param, $get, $post);
            }

            return call_user_func($this->functions[$name], $get, $post);
        }
    }

    /**
     * Main server
     *
     * This method creates the main server.
     *
     * @param string    $encoding       Server charset
     * @param int       $format         Response format
     * @param mixed     $param          Supplemental parameter
     *
     * @return bool
     */
    public function serve(string $encoding = 'UTF-8', int $format = self::DEFAULT_RESPONSE, $param = null): bool
    {
        if (!in_array($format, [self::XML_RESPONSE, self::JSON_RESPONSE])) {
            $format = self::DEFAULT_RESPONSE;
        }

        $get  = $_GET ?: [];
        $post = $_POST ?: [];

        switch ($format) {
            case self::XML_RESPONSE:
                if (!isset($_REQUEST['f'])) {
                    $this->rsp->status = 'failed';
                    $this->rsp->message('No function given');
                    $this->getXML($encoding);

                    return false;
                }

                if (!isset($this->functions[$_REQUEST['f']])) {
                    $this->rsp->status = 'failed';
                    $this->rsp->message('Function does not exist');
                    $this->getXML($encoding);

                    return false;
                }

                try {
                    $res = $this->callFunction($_REQUEST['f'], $get, $post, $param);
                } catch (Exception $e) {
                    $this->rsp->status = 'failed';
                    $this->rsp->message($e->getMessage());
                    $this->getXML($encoding);

                    return false;
                }

                $this->rsp->status = 'ok';
                $this->rsp->insertNode($res);
                $this->getXML($encoding);

                return true;

            case self::JSON_RESPONSE:
                if (!isset($_REQUEST['f'])) {
                    $this->json = [
                        'success' => false,
                        'message' => 'No function given',
                    ];
                    $this->getJSON($encoding);

                    return false;
                }

                if (!isset($this->functions[$_REQUEST['f']])) {
                    $this->json = [
                        'success' => false,
                        'message' => 'Function does not exist',
                    ];
                    $this->getJSON($encoding);

                    return false;
                }

                try {
                    $res = $this->callFunction($_REQUEST['f'], $get, $post, $param);
                } catch (Exception $e) {
                    $this->json = [
                        'success' => false,
                        'message' => $e->getMessage(),
                    ];
                    $this->getJSON($encoding);

                    return false;
                }

                $this->json = [
                    'success' => true,
                    'payload' => $res,
                ];
                $this->getJSON($encoding);

                return true;
        }

        return false;
    }

    /**
     * Stream the XML data (header and body)
     *
     * @param      string  $encoding  The encoding
     */
    private function getXML(string $encoding = 'UTF-8')
    {
        header('Content-Type: text/xml; charset=' . $encoding);
        echo $this->rsp->toXML(true, $encoding);
    }

    /**
     * Stream the JSON data (header and body)
     *
     * @param      string  $encoding  The encoding
     */
    private function getJSON(string $encoding = 'UTF-8')
    {
        header('Content-Type: application/json; charset=' . $encoding);
        echo json_encode($this->json, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES);
    }
}
