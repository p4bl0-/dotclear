<?php
/**
 * @brief maintenance, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use dcCore;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\XmlTag;
use Exception;

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

/**
@ingroup PLUGIN_MAINTENANCE
@nosubgrouping
@brief Maintenance plugin rest service class.

Serve maintenance methods via Dotclear's rest API
 */
class Rest
{
    /**
     * Serve method to do step by step task for maintenance.
     *
     * @param      dcCore     $core   dcCore instance
     * @param      array      $get    cleaned $_GET
     * @param      array      $post   cleaned $_POST
     *
     * @throws     Exception  (description)
     *
     * @return     XmlTag     XML representation of response.
     */
    public static function step(dcCore $core, array $get, array $post): XmlTag
    {
        if (!isset($post['task'])) {
            throw new Exception('No task ID');
        }
        if (!isset($post['code'])) {
            throw new Exception('No code ID');
        }

        $maintenance = new Maintenance();
        if (($task = $maintenance->getTask($post['task'])) === null) {
            throw new Exception('Unknown task ID');
        }

        $task->code((int) $post['code']);
        if (($code = $task->execute()) === true) {
            $maintenance->setLog($task->id());
            $code = 0;
        }

        $rsp        = new XmlTag('step');
        $rsp->code  = $code;
        $rsp->title = Html::escapeHTML($task->success());

        return $rsp;
    }
}
