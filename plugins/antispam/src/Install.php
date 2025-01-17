<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use dcCore;
use dcNsProcess;
use Dotclear\Database\Structure;
use initAntispam;

class Install extends dcNsProcess
{
    private static ?string $module = null;

    public static function init(): bool
    {
        self::$module = basename(dirname(__DIR__));
        static::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->newVersion(self::$module, dcCore::app()->plugins->moduleInfo(self::$module, 'version'));

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        /* Database schema
        -------------------------------------------------------- */
        $schema = new Structure(dcCore::app()->con, dcCore::app()->prefix);

        $schema->{initAntispam::SPAMRULE_TABLE_NAME}
            ->rule_id('bigint', 0, false)
            ->blog_id('varchar', 32, true)
            ->rule_type('varchar', 16, false, "'word'")
            ->rule_content('varchar', 128, false)

            ->primary('pk_spamrule', 'rule_id')

            ->index('idx_spamrule_blog_id', 'btree', 'blog_id')
            ->reference('fk_spamrule_blog', 'blog_id', 'blog', 'blog_id', 'cascade', 'cascade')
        ;

        if ($schema->driver() === 'pgsql') {
            $schema->{initAntispam::SPAMRULE_TABLE_NAME}->index('idx_spamrule_blog_id_null', 'btree', '(blog_id IS NULL)');
        }

        // Schema installation
        (new Structure(dcCore::app()->con, dcCore::app()->prefix))->synchronize($schema);

        // Creating default wordslist
        if (dcCore::app()->getVersion(self::$module) === null) {
            (new Filters\Words())->defaultWordsList();
        }

        dcCore::app()->blog->settings->get('antispam')->put('antispam_moderation_ttl', 0, 'integer', 'Antispam Moderation TTL (days)', false);

        return true;
    }
}
