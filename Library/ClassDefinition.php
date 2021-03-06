<?php

/*
 +----------------------------------------------------------------------+
 | Zephir Language                                                      |
 +----------------------------------------------------------------------+
 | Copyright (c) 2013-2014 Zephir Team                                  |
 +----------------------------------------------------------------------+
 | This source file is subject to version 1.0 of the MIT license,       |
 | that is bundled with this package in the file LICENSE, and is        |
 | available through the world-wide-web at the following url:           |
 | http://www.zephir-lang.com/license                                   |
 |                                                                      |
 | If you did not receive a copy of the MIT license and are unable      |
 | to obtain it through the world-wide-web, please send a note to       |
 | license@zephir-lang.com so we can mail you a copy immediately.       |
 +----------------------------------------------------------------------+
*/

namespace Zephir;

/**
 * ClassDefinition
 *
 * Represents a class/interface and their properties and methods
 */
class ClassDefinition
{
    protected $namespace;

    protected $name;

    protected $type = 'class';

    protected $extendsClass;

    protected $interfaces;

    protected $final;

    protected $abstract;

    protected $extendsClassDefinition;

    /**
     * @var ClassProperty[]
     */
    protected $properties = array();

    /**
     * @var ClassConstant[]
     */
    protected $constants = array();

    protected $methods = array();

    protected $dependencyRank = 0;

    /**
     * ClassDefinition
     *
     * @param string $namespace
     * @param string $name
     */
    public function __construct($namespace, $name)
    {
        $this->namespace = $namespace;
        $this->name = $name;
    }

    /**
     * Set the class' type (class/interface)
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the class type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the class name without namespace
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets if the class is final
     *
     * @param boolean $final
     */
    public function setIsFinal($final)
    {
        $this->final = $final;
    }

    /**
     * Sets if the class is final
     *
     * @param boolean $abstract
     */
    public function setIsAbstract($abstract)
    {
        $this->abstract = $abstract;
    }

    /**
     * Checks whether the class is abstract or not
     *
     * @return boolean
     */
    public function isAbstract()
    {
        return $this->abstract;
    }

    /**
     * Checks whether the class is abstract or not
     *
     * @return boolean
     */
    public function isFinal()
    {
        return $this->final;
    }

    /**
     * Returns the class name including its namespace
     *
     * @return string
     */
    public function getCompleteName()
    {
        return $this->namespace . '\\' . $this->name;
    }

    /**
     * Return the class namespace
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Sets the extended class
     *
     * @param string $extendsClass
     */
    public function setExtendsClass($extendsClass)
    {
        if (substr($extendsClass, 0, 1) == '\\') {
            $extendsClass = substr($extendsClass, 1);
        }

        $this->extendsClass = $extendsClass;
    }

    /**
     * Sets the implemented interfaces
     *
     * @param array $implementedInterfaces
     */
    public function setImplementsInterfaces(array $implementedInterfaces)
    {
        $interfaces = array();
        foreach ($implementedInterfaces as $implementedInterface) {
            if (substr($implementedInterface['value'], 0, 1) == '\\') {
                $implementedInterface['value'] = substr($implementedInterface['value'], 1);
            }
            $interfaces[] = $implementedInterface['value'];
        }

        $this->interfaces = $interfaces;
    }

    /**
     * Returns the extended class
     *
     * @return string
     */
    public function getExtendsClass()
    {
        return $this->extendsClass;
    }

    /**
     * Returns the implemented interfaces
     *
     * @return array
     */
    public function getImplementedInterfaces()
    {
        return $this->interfaces;
    }

    /**
     * Sets the class definition for the extended class
     *
     * @param $classDefinition
     */
    public function setExtendsClassDefinition($classDefinition)
    {
        $this->extendsClassDefinition = $classDefinition;
    }

    /**
     * Returns the class definition related to the extended class
     *
     * @return ClassDefinition
     */
    public function getExtendsClassDefinition()
    {
        return $this->extendsClassDefinition;
    }

    /**
     * Calculate the dependency rank of the class based on its dependencies
     *
     */
    public function calculateDependencyRank()
    {
        if ($this->extendsClassDefinition) {
            $classDefinition = $this->extendsClassDefinition;
            if (method_exists($classDefinition, 'increaseDependencyRank')) {
                $classDefinition->increaseDependencyRank($this->dependencyRank * 2);
            }
        }
    }

    /**
     * A class definition calls this method to mark this class as a dependency of another
     *
     * @param int $rank
     */
    public function increaseDependencyRank($rank)
    {
        $this->dependencyRank += ($rank + 1);
    }

    /**
     * Returns the dependency rank for this class
     *
     * @return int
     */
    public function getDependencyRank()
    {
        return $this->dependencyRank;
    }

    /**
     * Adds a property to the definition
     *
     * @param ClassProperty $property
     * @throws CompilerException
     */
    public function addProperty(ClassProperty $property)
    {
        if (isset($this->properties[$property->getName()])) {
            throw new CompilerException("Property '" . $property->getName() . "' was defined more than one time", $property->getOriginal());
        }

        $this->properties[$property->getName()] = $property;
    }

    /**
     * Adds a constant to the definition
     *
     * @param ClassConstant $constant
     * @throws CompilerException
     */
    public function addConstant(ClassConstant $constant)
    {
        if (isset($this->constants[$constant->getName()])) {
            throw new CompilerException("Constant '" . $constant->getName() . "' was defined more than one time");
        }

        $this->constants[$constant->getName()] = $constant;
    }

    /**
     * Checks if a class definition has a property
     *
     * @param string $name
     * @return boolean
     */
    public function hasProperty($name)
    {
        if (isset($this->properties[$name])) {
            return true;
        } else {
            $extendsClassDefinition = $this->extendsClassDefinition;
            if ($extendsClassDefinition) {
                if ($extendsClassDefinition->hasProperty($name)) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Returns a method definition by its name
     *
     * @param string string
     * @return boolean|ClassProperty
     */
    public function getProperty($propertyName)
    {
        if (isset($this->properties[$propertyName])) {
            return $this->properties[$propertyName];
        }

        $extendsClassDefinition = $this->extendsClassDefinition;
        if ($extendsClassDefinition) {
            if ($extendsClassDefinition->hasProperty($propertyName)) {
                return $extendsClassDefinition->getProperty($propertyName);
            }
        }
        return false;
    }

    /**
     * Checks if class definition has a property
     *
     * @param string $name
     */
    public function hasConstant($name)
    {
        if (isset($this->constants[$name])) {
            return true;
        }
        /**
         * @todo add code to check if constant is defined in interfaces
         */
        return false;
    }

    /**
     * Returns a constant definition by its name
     *
     * @param string $constantName
     * @return bool|ClassConstant
     */
    public function getConstant($constantName)
    {
        if (!is_string($constantName)) {
            throw new \InvalidArgumentException('$constantName must be string type');
        }

        if (empty($constantName)) {
            throw new \InvalidArgumentException('$constantName must not be empty: '.$constantName);
        }

        if (isset($this->constants[$constantName])) {
            return $this->constants[$constantName];
        }

        /**
         * @todo add code to get constant from interfaces
         */
        return false;
    }

    /**
     * Adds a method to the class definition
     *
     * @param ClassMethod $method
     * @param array $statement
     */
    public function addMethod(ClassMethod $method, $statement = null)
    {
        $methodName = strtolower($method->getName());
        if (isset($this->methods[$methodName])) {
            throw new CompilerException("Method '" . $method->getName() . "' was defined more than one time", $statement);
        }

        $this->methods[$methodName] = $method;
    }

    /**
     * Returns all properties defined in the class
     *
     * @return ClassProperty[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Returns all constants defined in the class
     *
     * @return ClassConstant[]
     */
    public function getConstants()
    {
        return $this->constants;
    }

    /**
     * Returns all methods defined in the class
     *
     * @param string
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Checks if the class implements an specific name
     *
     * @param string string
     * @return boolean
     */
    public function hasMethod($methodName)
    {
        $methodNameLower = strtolower($methodName);
        foreach ($this->methods as $name => $method) {
            if ($methodNameLower == $name) {
                return true;
            }
        }

        $extendsClassDefinition = $this->extendsClassDefinition;
        if ($extendsClassDefinition) {
            if ($extendsClassDefinition->hasMethod($methodName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a method by its name
     *
     * @param string string
     * @return boolean
     */
    public function getMethod($methodName)
    {
        $methodNameLower = strtolower($methodName);
        foreach ($this->methods as $name => $method) {
            if ($methodNameLower == $name) {
                return $method;
            }
        }

        $extendsClassDefinition = $this->extendsClassDefinition;
        if ($extendsClassDefinition) {
            if ($extendsClassDefinition->hasMethod($methodName)) {
                return $extendsClassDefinition->getMethod($methodName);
            }
        }
        return false;
    }

    /**
     * Returns the name of the zend_class_entry according to the class name
     *
     * @return string
     */
    public function getClassEntry()
    {
        return strtolower(str_replace('\\', '_', $this->namespace) . '_' . $this->name) . '_ce';
    }

    /**
     * Returns a valid namespace to be used in C-sources
     *
     * @return string
     */
    public function getCNamespace()
    {
        return str_replace('\\', '_', $this->namespace);
    }

    /**
     * Returns a valid namespace to be used in C-sources
     *
     * @return string
     */
    public function getNCNamespace()
    {
        return str_replace('\\', '\\\\', $this->namespace);
    }

    /**
     * Class name without namespace prefix for class registration
     *
     * @param string $namespace
     * @return string
     */
    public function getSCName($namespace)
    {
        return str_replace($namespace . "_", "", strtolower(str_replace('\\', '_', $this->namespace) . '_' . $this->name));
    }

    /**
     * Checks if a class implements an interface
     *
     * @param ClassDefinition $classDefinition
     * @param ClassDefinition $interfaceDefinition
     */
    public function checkInterfaceImplements($classDefinition, $interfaceDefinition)
    {
        foreach ($interfaceDefinition->getMethods() as $method) {
            if (!$classDefinition->hasMethod($method->getName())) {
                if ($interfaceDefinition instanceof ClassDefinition) {
                    throw new CompilerException("Class " . $classDefinition->getCompleteName() . " must implement method: " . $method->getName() . " defined on interface: " . $interfaceDefinition->getCompleteName());
                } else {
                    throw new CompilerException("Class " . $classDefinition->getCompleteName() . " must implement method: " . $method->getName() . " defined on interface: " . $interfaceDefinition->getName());
                }
            }
        }
    }

    /**
     * Compiles a class/interface
     *
     * @param CompilationContext $compilationContext
     */
    public function compile(CompilationContext $compilationContext)
    {
        $compilationContext->classDefinition = $this;

        $codePrinter = $compilationContext->codePrinter;

        /**
         * The ZEPHIR_INIT_CLASS defines properties and constants exported by the class
         */
        $codePrinter->output('ZEPHIR_INIT_CLASS(' . $this->getCNamespace() . '_' . $this->getName() . ') {');
        $codePrinter->outputBlankLine();

        $codePrinter->increaseLevel();

        /**
         * Method entry
         */
        $methods = $this->methods;
        if (count($methods)) {
            $methodEntry = strtolower($this->getCNamespace()) . '_' . strtolower($this->getName()) . '_method_entry';
        } else {
            $methodEntry = 'NULL';
        }

        $namespace = $compilationContext->config->get('namespace');

        $abstractFlag = '0';
        if ($this->isAbstract()) {
            $abstractFlag = 'ZEND_ACC_EXPLICIT_ABSTRACT_CLASS';
        }

        /**
         * Register the class with extends + interfaces
         */
        $classExtendsDefinition = null;
        if ($this->extendsClass) {

            $classExtendsDefinition = $this->extendsClassDefinition;
            if ($classExtendsDefinition instanceof ClassDefinition) {
                $classEntry = $classExtendsDefinition->getClassEntry();
            } else {
                $classEntry = $this->getClassEntryByClassName($classExtendsDefinition->getName());
            }

            if ($this->getType() == 'class') {
                $codePrinter->output('ZEPHIR_REGISTER_CLASS_EX(' . $this->getNCNamespace() . ', ' . $this->getName() . ', ' . $namespace  . ', ' . strtolower($this->getSCName($namespace)) . ', ' . $classEntry . ', ' . $methodEntry . ', ' . $abstractFlag . ');');
                $codePrinter->outputBlankLine();
            } else {
                $codePrinter->output('ZEPHIR_REGISTER_INTERFACE_EX(' . $this->getNCNamespace() . ', ' . $this->getName() . ', ' . $namespace  . ', ' . strtolower($this->getSCName($namespace)) . ', ' . $classEntry . ', ' . $methodEntry . ');');
                $codePrinter->outputBlankLine();
            }
        } else {
            if ($this->getType() == 'class') {
                $codePrinter->output('ZEPHIR_REGISTER_CLASS(' . $this->getNCNamespace() . ', ' . $this->getName() . ', ' . $namespace . ', ' . strtolower($this->getSCName($namespace)) . ', ' . $methodEntry . ', ' . $abstractFlag . ');');
            } else {
                $codePrinter->output('ZEPHIR_REGISTER_INTERFACE(' . $this->getNCNamespace() . ', ' . $this->getName() . ', ' . $namespace . ', ' . strtolower($this->getSCName($namespace)) . ', ' . $methodEntry . ');');
            }
            $codePrinter->outputBlankLine();
        }

        /**
         * Compile properties
         */
        foreach ($this->getProperties() as $property) {
            $docBlock = $property->getDocBlock();
            if ($docBlock) {
                $codePrinter->outputDocBlock($docBlock, false);
            }
            $property->compile($compilationContext);
        }

        /**
         * Compile constants
         */
        foreach ($this->getConstants() as $constant) {
            $docBlock = $constant->getDocBlock();
            if ($docBlock) {
                $codePrinter->outputDocBlock($docBlock);
            }
            $constant->compile($compilationContext);
        }

        /**
         * Implemented interfaces
         */
        $interfaces = $this->interfaces;
        $compiler = &$compilationContext->compiler;

        if (is_array($interfaces)) {

            $codePrinter->outputBlankLine(true);

            foreach ($interfaces as $interface) {
                $classEntry = false;

                if ($interface[0] != '\\' && $compiler->isInterface($compilationContext->classDefinition->getNamespace().'\\'.$interface)) {
                    $interface = $compilationContext->classDefinition->getNamespace().'\\'.$interface;

                    $classInterfaceDefinition = $compiler->getClassDefinition($interface);
                    $classEntry = $classInterfaceDefinition->getClassEntry();
                } else if ($compiler->isInterface($interface)) {
                    $classInterfaceDefinition = $compiler->getClassDefinition($interface);
                    $classEntry = $classInterfaceDefinition->getClassEntry();
                } else if ($compiler->isInternalInterface($interface)) {
                    $classInterfaceDefinition = $compiler->getInternalClassDefinition($interface);
                    $classEntry = $this->getClassEntryByClassName($classInterfaceDefinition->getName());
                }

                if (!$classEntry) {
                    throw new CompilerException("Cannot locate interface " . $interface . " when implementing interfaces on " . $this->getCompleteName());
                }

                /**
                 * We dont' check if abstract classes implement the methods in their interfaces
                 */
                if (!$this->isAbstract()) {
                    $this->checkInterfaceImplements($this, $classInterfaceDefinition);
                }

                $codePrinter->output('zend_class_implements(' . $this->getClassEntry() . ' TSRMLS_CC, 1, ' . $classEntry . ');');
            }
        }

        if (!$this->isAbstract()) {

            /**
             * Interfaces in extended classes may have
             */
            if ($classExtendsDefinition) {

                if ($classExtendsDefinition instanceof ClassDefinition) {
                    $interfaces = $classExtendsDefinition->getImplementedInterfaces();
                    if (is_array($interfaces)) {
                        foreach ($interfaces as $interface) {

                            $classInterfaceDefinition = null;
                            if ($compiler->isInterface($interface)) {
                                $classInterfaceDefinition = $compiler->getClassDefinition($interface);
                            } else {
                                if ($compiler->isInternalInterface($interface)) {
                                    $classInterfaceDefinition = $compiler->getInternalClassDefinition($interface);
                                }
                            }

                            if ($classInterfaceDefinition) {
                                $this->checkInterfaceImplements($this, $classInterfaceDefinition);
                            }

                        }
                    }
                }

            }
        }

        $codePrinter->outputBlankLine();
        $codePrinter->output('return SUCCESS;');

        $codePrinter->outputBlankLine();
        $codePrinter->decreaseLevel();

        $codePrinter->output('}');
        $codePrinter->outputBlankLine();

        /**
         * Compile methods
         */
        foreach ($methods as $method) {

            $docBlock = $method->getDocBlock();
            if ($docBlock) {
                $codePrinter->outputDocBlock($docBlock);
            }

            if ($this->getType() == 'class') {
                $codePrinter->output('PHP_METHOD(' . $this->getCNamespace() . '_' . $this->getName() . ', ' . $method->getName() . ') {');
                $codePrinter->outputBlankLine();

                $method->compile($compilationContext);

                $codePrinter->output('}');
                $codePrinter->outputBlankLine();
            } else {
                $codePrinter->output('ZEPHIR_DOC_METHOD(' . $this->getCNamespace() . '_' . $this->getName() . ', ' . $method->getName() . ');');
                $codePrinter->outputBlankLine();
            }
        }

        /**
         * Create a code printer for the header file
         */
        $codePrinter = new CodePrinter();

        $codePrinter->outputBlankLine();
        $codePrinter->output('extern zend_class_entry *' . $this->getClassEntry() . ';');
        $codePrinter->outputBlankLine();

        $codePrinter->output('ZEPHIR_INIT_CLASS(' . $this->getCNamespace() . '_' . $this->getName() . ');');
        $codePrinter->outputBlankLine();

        if ($this->getType() == 'class') {
            if (count($methods)) {
                foreach ($methods as $method) {
                    $codePrinter->output('PHP_METHOD(' . $this->getCNamespace() . '_' . $this->getName() . ', ' . $method->getName() . ');');
                }
                $codePrinter->outputBlankLine();
            }
        }

        /**
         * Create argument info
         */
        foreach ($methods as $method) {

            $parameters = $method->getParameters();
            if (count($parameters)) {
                $codePrinter->output('ZEND_BEGIN_ARG_INFO_EX(arginfo_' . strtolower($this->getCNamespace() . '_' . $this->getName() . '_' . $method->getName()) . ', 0, 0, ' . $method->getNumberOfRequiredParameters() . ')');
                foreach ($parameters->getParameters() as $parameter) {
                    $codePrinter->output('  ZEND_ARG_INFO(0, ' . $parameter['name'] . ')');
                }
                $codePrinter->output('ZEND_END_ARG_INFO()');
                $codePrinter->outputBlankLine();
            }

        }

        if (count($methods)) {
            $codePrinter->output('ZEPHIR_INIT_FUNCS(' . strtolower($this->getCNamespace() . '_' . $this->getName()) . '_method_entry) {');
            foreach ($methods as $method) {
                $parameters = $method->getParameters();
                if ($this->getType() == 'class') {
                    if (count($parameters)) {
                        $codePrinter->output("\t" . 'PHP_ME(' . $this->getCNamespace() . '_' . $this->getName() . ', ' . $method->getName() . ', arginfo_' . strtolower($this->getCNamespace() . '_' . $this->getName() . '_' . $method->getName()) . ', ' . $method->getModifiers() . ')');
                    } else {
                        $codePrinter->output("\t" . 'PHP_ME(' . $this->getCNamespace() . '_' . $this->getName() . ', ' . $method->getName() . ', NULL, ' . $method->getModifiers() . ')');
                    }
                } else {
                    if ($method->isStatic()) {
                        if (count($parameters)) {
                            $codePrinter->output("\t" . 'ZEND_FENTRY(' . $method->getName() . ', NULL, arginfo_' . strtolower($this->getCNamespace() . '_' . $this->getName() . '_' . $method->getName()) . ', ZEND_ACC_STATIC|ZEND_ACC_ABSTRACT|ZEND_ACC_PUBLIC)');
                        } else {
                            $codePrinter->output("\t" . 'ZEND_FENTRY(' . $method->getName() . ', NULL, NULL, ZEND_ACC_STATIC|ZEND_ACC_ABSTRACT|ZEND_ACC_PUBLIC)');
                        }
                    } else {
                        if (count($parameters)) {
                            $codePrinter->output("\t" . 'PHP_ABSTRACT_ME(' . $this->getCNamespace() . '_' . $this->getName() . ', ' . $method->getName() . ', arginfo_' . strtolower($this->getCNamespace() . '_' . $this->getName() . '_' . $method->getName()) . ')');
                        } else {
                            $codePrinter->output("\t" . 'PHP_ABSTRACT_ME(' . $this->getCNamespace() . '_' . $this->getName() . ', ' . $method->getName() . ', NULL)');
                        }
                    }
                }
            }
            $codePrinter->output('  PHP_FE_END');
            $codePrinter->output('};');
        }

        $compilationContext->headerPrinter = $codePrinter;
    }

    /**
     * Convert Class/Interface name to C ClassEntry
     *
     * @param string $className
     * @return string
     * @throws CompilerException
     */
    protected static function getClassEntryByClassName($className)
    {
        switch (strtolower($className)) {
            /**
             * Zend classes
             */
            case 'exception':
                $classEntry = 'zend_exception_get_default(TSRMLS_C)';
                break;

            /**
             * Zend interfaces (Zend/zend_interfaces.h)
             */
            case 'iterator':
                $classEntry = 'zend_ce_iterator';
                break;
            case 'arrayaccess':
                $classEntry = 'zend_ce_arrayaccess';
                break;
            case 'serializable':
                $classEntry = 'zend_ce_serializable';
                break;
            case 'iteratoraggregate':
                $classEntry = 'zend_ce_aggregate';
                break;

            /**
             * SPL Exceptions
             */
            case 'logicexception':
                $classEntry = 'spl_ce_LogicException';
                break;
            case 'badfunctioncallexception':
                $classEntry = 'spl_ce_BadFunctionCallException';
                break;
            case 'badmethodcallexception':
                $classEntry = 'spl_ce_BadMethodCallException';
                break;
            case 'domainexception':
                $classEntry = 'spl_ce_DomainException';
                break;
            case 'invalidargumentexception':
                $classEntry = 'spl_ce_InvalidArgumentException';
                break;
            case 'lengthexception':
                $classEntry = 'spl_ce_LengthException';
                break;
            case 'outofrangeexception':
                $classEntry = 'spl_ce_OutOfRangeException';
                break;
            case 'runtimeexception':
                $classEntry = 'spl_ce_RuntimeException';
                break;
            case 'outofboundsexception':
                $classEntry = 'spl_ce_OutOfBoundsException';
                break;
            case 'overflowexception':
                $classEntry = 'spl_ce_OverflowException';
                break;
            case 'rangeexception':
                $classEntry = 'spl_ce_RangeException';
                break;
            case 'underflowexception':
                $classEntry = 'spl_ce_UnderflowException';
                break;
            case 'unexpectedvalueexception':
                $classEntry = 'spl_ce_UnexpectedValueException';
                break;

            /**
             * SPL Iterators Interfaces (spl/spl_iterators.h)
             */
            case 'recursiveiterator':
                $classEntry = 'spl_ce_RecursiveIterator';
                break;
            case 'recursiveiteratoriterator':
                $classEntry = 'spl_ce_RecursiveIteratorIterator';
                break;
            case 'recursivetreeiterator':
                $classEntry = 'spl_ce_RecursiveTreeIterator';
                break;
            case 'filteriterator':
                $classEntry = 'spl_ce_FilterIterator';
                break;
            case 'recursivefilteriterator':
                $classEntry = 'spl_ce_RecursiveFilterIterator';
                break;
            case 'parentiterator':
                $classEntry = 'spl_ce_ParentIterator';
                break;
            case 'seekableiterator':
                $classEntry = 'spl_ce_SeekableIterator';
                break;
            case 'limititerator':
                $classEntry = 'spl_ce_LimitIterator';
                break;
            case 'cachingiterator':
                $classEntry = 'spl_ce_CachingIterator';
                break;
            case 'recursivecachingiterator':
                $classEntry = 'spl_ce_RecursiveCachingIterator';
                break;
            case 'outeriterator':
                $classEntry = 'spl_ce_OuterIterator';
                break;
            case 'iteratoriterator':
                $classEntry = 'spl_ce_IteratorIterator';
                break;
            case 'norewinditerator':
                $classEntry = 'spl_ce_NoRewindIterator';
                break;
            case 'infiniteiterator':
                $classEntry = 'spl_ce_InfiniteIterator';
                break;
            case 'emptyiterator':
                $classEntry = 'spl_ce_EmptyIterator';
                break;
            case 'appenditerator':
                $classEntry = 'spl_ce_AppendIterator';
                break;
            case 'regexiterator':
                $classEntry = 'spl_ce_RegexIterator';
                break;
            case 'recursiveregexiterator':
                $classEntry = 'spl_ce_RecursiveRegexIterator';
                break;
            case 'countable':
                $classEntry = 'spl_ce_Countable';
                break;
            case 'callbackfilteriterator':
                $classEntry = 'spl_ce_CallbackFilterIterator';
                break;
            case 'recursivecallbackfilteriterator':
                $classEntry = 'spl_ce_RecursiveCallbackFilterIterator';
                break;
            default:
                throw new CompilerException('Unknown class entry for "' . $className . '"');
        }
        return $classEntry;
    }
}
