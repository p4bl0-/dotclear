<?php
/**
 * @class TplNode
 *
 * Template nodes, for parsing purposes
 * Generic list node, this one may only be instanciated once for root element
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Template;

use ArrayObject;

class TplNode
{
    /**
     * Basic tree structure : links to parent, children forrest
     *
     * @var null|TplNode|TplNodeBlock|TplNodeBlockDefinition|TplNodeText|TplNodeValue|TplNodeValueParent
     */
    protected $parentNode;

    /**
     * Node children
     *
     * @var ArrayObject
     */
    protected $children;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->children   = new ArrayObject();
        $this->parentNode = null;
    }

    /**
     * Indicates that the node is closed.
     */
    public function setClosing(): void
    {
        // Nothing to do at this level
    }

    /**
     * Returns compiled block
     *
     * @param  Template     $tpl    The current template engine instance
     *
     * @return     string
     */
    public function compile(Template $tpl)
    {
        $res = '';
        foreach ($this->children as $child) {
            $res .= $child->compile($tpl);
        }

        return $res;
    }

    /**
     * Add a children to current node.
     *
     * @param      TplNode|TplNodeBlock|TplNodeBlockDefinition|TplNodeText|TplNodeValue|TplNodeValueParent  $child  The child
     */
    public function addChild($child)
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    /**
     * Set current node children.
     *
     * @param      ArrayObject  $children  The children
     */
    public function setChildren($children)
    {
        $this->children = $children;
        foreach ($this->children as $child) {
            $child->setParent($this);
        }
    }

    #

    /**
     * Defines parent for current node.
     *
     * @param      null|TplNode|TplNodeBlock|TplNodeBlockDefinition|TplNodeValue|TplNodeValueParent  $parent  The parent
     */
    protected function setParent($parent)
    {
        $this->parentNode = $parent;
    }

    /**
     * Retrieves current node parent.
     *
     * If parent is root node, null is returned
     *
     * @return     null|TplNode|TplNodeBlock|TplNodeBlockDefinition|TplNodeValue|TplNodeValueParent  The parent.
     */
    public function getParent()
    {
        return $this->parentNode;
    }

    /**
     * Gets the tag.
     *
     * @return     string  The tag.
     */
    public function getTag(): string
    {
        return 'ROOT';
    }
}
