<?php

namespace Zenya\Api\Entity;

use Zenya\Api\Entity,
    Zenya\Api\Entity\EntityInterface,
    Zenya\Api\Reflection,
    Zenya\Api\Router;

/**
 * Represents a class based entity resource.
 */
class EntityClass extends Entity implements EntityInterface
{
    protected $controller;

    private $reflection;

    /**
     * Sets and returns a reflection of the entity class.
     *
     * @return \ReflectionClass|false
     */
    public function reflectedClass()
    {
        if (null == $this->reflection) {
            try {
                $this->reflection = new \ReflectionClass(
                    $this->controller['name']
                );
            } catch (\Exception $Exception) {
                throw new \RuntimeException("Resource entity not (yet) implemented.", 501);
            }
        }


        return $this->reflection;
    }

    /**
     * {@inheritdoc}
     */
    public function append(array $defs)
    {
        parent::_append($defs);

        if (isset($defs['controller'])) {
            $this->controller = $defs['controller'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function underlineCall(Router $route)
    {
        $name = $this->controller['name'];
        $args = $this->controller['args'];

        // if (!isset($entity->controller['name'])) {
        //     $entity->controller['name'] = $route->controller_name;
        // }

        // just created a deependency here!!!!!
        // if (!isset($args)) {
        //     $args = $route->controller_args;
        // }

        $method = $this->getMethod($route);

        $params = $this->getRequiredParams($method, $route->getMethod(), $route->getParams());

        // attach late listeners @ post-processing
        #$this->addAllListeners('resource', 'early');

        #echo '<pre>';print_r($this->toArray());exit;

        return call_user_func_array(
          array(
            new $name($args),
            $route->getAction()),
            $params
          );
    }

    /**
     * {@inheritdoc}
     */
    public function _parseDocs()
    {
        // class doc
        $docs = Reflection::parsePhpDoc(
            $this->reflectedClass()->getDocComment()
        );

        $actions = $this->getActions();

        // doc for all methods
        foreach ($this->getMethods() as $key => $method) {
          if ( $key = array_search($method->name, $actions) ) {
            $doc = $method->getDocComment();
            $docs['methods'][$key] =
                Reflection::parsePhpDoc( $doc );
          }
        }

        return $docs;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(Router $route)
    {
        $name = $route->getAction();
        if (false === $this->reflectedClass()->hasMethod($name)) {
            throw new \InvalidArgumentException("Invalid resource's method ({$route->getMethod()}) specified.", 405);
        }

        return $this->reflectedClass()->getMethod($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        $funcs = array();
        foreach ($this->getMethods() as $ref) {
            $funcs[] = $ref->name;
        }
        $routes = Router::$actions;

        return array_intersect($routes, $funcs);

        $all= array_intersect($routes, $funcs);

        return $all+$this->overrides;
    }

    /**
     * Returns the class methods. Use internalaly.
     * @return array An array of \ReflectionMethod objects.
     */
    public function getMethods()
    {
        return $this->reflectedClass()->getMethods(
            \ReflectionMethod::IS_STATIC | \ReflectionMethod::IS_PUBLIC
        );
    }

}
