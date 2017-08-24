<?php
namespace CodeRefactor;

use PhpParser\Builder;
use PhpParser\Node\Stmt;
use PhpParser\Comment;


class CodeFile
{
    protected $offset = 0;
    
    public $filename = '';
    public $comments = [];
    protected $stmts = [];
    protected $namespaces = [];
    protected $classes = [];
    protected $functions = [];
    
    public function __construct($filename, array $stmts = [])
    {
        $this->filename = $filename;
        $this->offset = 0;
        foreach ($stmts as $stmt) {
            $this->addStmt($stmt);
        }
    }
    
    public static function getStmtNode($node)
    {
        if (method_exists($node, 'getNode')) {
            $node = $node->getNode();
        }
        return $node;
    }
    
    /**
     * Returns the all stmts.
     */
    public function getStmts()
    {
        return array_filter($this->stmts);
    }
    
    /**
     * Adds a statement.
     */
    public function addStmt($stmt) {
        if (method_exists($stmt, 'isMixinCode')) {
            $name = $stmt->getName();
        } else {
            $stmt = self::getStmtNode($stmt);
            $name = $stmt->name;
        }
        $type = $stmt->getType();
        switch ($type) {
            case 'Stmt_Namespace':
                $this->namespaces[$name] = $this->offset;
                break;
            case 'Stmt_Class':
            case 'Stmt_Interface':
            case 'Stmt_Trait':
                $stmt = new Code\ClassCode($stmt);
            case 'ClassCode':
                $this->classes[$name] = $this->offset;
                break;
            case 'Stmt_Function':
                $stmt = new Code\FunctionCode($stmt);
            case 'FunctionCode':
                $name = strtolower($name);
                $this->functions[$name] = $this->offset;
                break;
        }
        $this->stmts[$this->offset ++] = $stmt;
        return $this;
    }
    
    public function addComment($text)
    {
        if ($this->comments) {
            $this->comments[] = "\n";
        }
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $this->comments[] = rtrim($line);
        }
        return $this;
    }
    
    public function getDocComment()
    {
        if ($this->comments) {
            $text = implode("\n * ", $this->comments);
            return new Comment\Doc(sprintf("/**\n * %s\n */", $text));
        }
    }
    
    public function removeCode($name, $type = 'classes')
    {
        if (isset($this->$type) && is_array($this->$type)) {
            $components = $this->$type;
            if (isset($components[$name])) {
                $offset = $components[$name];
                $this->stmts[$offset] = null;
            }
        }
        return $this;
    }
    
    public function getCodes($type = 'classes')
    {
        $result = [];
        if (isset($this->$type) && is_array($this->$type)) {
            $components = $this->$type;
            foreach ($components as $name => $offset) {
                $offset = $components[$name];
                $result[$name] = $this->stmts[$offset];
            }
        }
        return $result;
    }
    
    public function getClass($name = false)
    {
        if (empty($name)) {
            return $this->getCodes('classes');
        } elseif (isset($this->classes[$name])) {
            $offset = $this->classes[$name];
            return $this->stmts[$offset];
        }
    }
    
    public function setClass($name, $node = null)
    {
        if ($node instanceof ClassCode) {
            $node->setName($name);
        } elseif ($node instanceof Stmt\ClassLike) {
            $node->name = $name;
            $node = new ClassCode($node);
        } else {
            $stmt = new Builder\Class_($name);
            $node = new ClassCode($stmt->getNode());
        }
        $this->addStmt($node);
        return $this;
    }
    
    public function getFunction($name = false)
    {
        if (empty($name)) {
            return $this->getCodes('functions');
        }
        $name = strtolower($name);
        if (isset($this->functions[$name])) {
            $offset = $this->functions[$name];
            return $this->stmts[$offset];
        }
    }
    
    public function setFunction($name, $node = null)
    {
        if ($node instanceof FunctionCode) {
            $node->setName($name);
        } elseif ($node instanceof Stmt\Function_) {
            $node->name = $name;
            $node = new FunctionCode($node);
        } else {
            $stmt = new Builder\Function_($name);
            $node = new FunctionCode($stmt->getNode());
        }
        $this->addStmt($node);
        return $this;
    }
}