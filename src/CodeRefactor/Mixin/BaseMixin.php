<?php
/**
 * CodeRefactor
 * @author        Ryan Liu <http://azhai.oschina.io>
 * @copyright (c) 2017 MIT License
 */

namespace CodeRefactor\Mixin;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;

trait BaseMixin
{
    /**
     * Returns the class name.
     */
    public function getType()
    {
        $class_name = get_class($this);
        return ltrim(strrchr($class_name, '\\'), '\\');
    }
    
    /**
     * Returns the all stmts.
     */
    public function getStmts()
    {
        return array_filter($this->stmts);
    }
    
    /**
     * 复制文档式注释
     */
    public function dupDocComment()
    {
        if ($doc_comment = $this->getDocComment(false)) {
            $this->setDocComment($doc_comment);
        }
    }
    
    /**
     * 复制所有文档式注释
     */
    public function dupAllDocComments()
    {
        if ($doc_comments = $this->getDocComment(true)) {
            foreach ($doc_comments as $doc_comment) {
                $this->addComment($doc_comment);
            }
        }
    }
    
    /**
     * 查找和修改下级代码对象
     *
     * @param string        $type     代码类型
     * @param bool/string   $pattern   名称正则式
     * @param null/function $callback 修改回调函数
     * @return array
     */
    public function find($type, $pattern = false, $callback = null)
    {
        if (!isset($this->{$type})) {
            return [];
        }
        $components = to_array($this->{$type}, false);
        $result = [];
        foreach ($components as $name => $node) {
            if (empty($pattern) || preg_match($pattern, $name)) {
                if (!empty($callback)) {
                    $node = exec_function_array($callback, [$node, $this]);
                }
                $result[$name] = $node;
            }
        }
        return $result;
    }
}