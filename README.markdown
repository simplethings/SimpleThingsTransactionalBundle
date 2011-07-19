# SimpleThings TransactionalBundle

Wraps calls to controllers into a transaction, be it Doctrine DBAL or Persistence Managers (ORM, MongoDB, CouchDB).
Configuration is done via routing parameters or through a list of controllers/actions configured in the
extension config.

## Installation

See at the end of this document.

## Problem

Symfony2 allows to nest controllers into each other in unlimited amounts. These controllers can all modify and save
data, probably with different transactional needs. The Doctrine persistence solutions (ORM, MongoDB, CouchDB) use a transactional write-behind
mechanism to flush changes in batches, best executed at the end of the master request. If each controller
or model service handles transactions themselves then you probably overuse the flush operation, which
can lead to inconsistencies and performance penalities.

These flushes should not be executed in the model/services but should be handled by the controller layer, because it knows when all operations are done.

## How it works

For every Doctrine DBAL connection, every EntityManager and every DocumentManager the Transactional Bundle
creates a service that implements a transactions manager interface:

    interface TransactionManagerInterface
    {
        function beginTransaction();
        function commit();
        function rollBack();
    }

With the transactional bundle the following workflow is applied to an action that is marked
as transactional (by default always if POST, PUT, DELETE, PATCH request is found).

0. Detect which Transaction Manager(s) should wrap the to-be-excecuted action.
1. A transaction is started before the controller is called.
2. The controller execution is wrapped in a try-catch block
3. On successful response generation (status code < 400) the transaction is committed. This includes a call to EntityManager::flush or DocumentManager::flush in case of an orm, mongodb or couchdb "transaction".
4. On status-code >= 400 the transaction is rolled back.
5. If an exception is thrown the transaction is rolled back.

Each transaction manager is named like the manager it belongs to:

    doctrine.orm.default_entity_manager => simplethings_tx.orm.default
    doctrine_mongodb.odm.default_document_manager => simplethings_tx.mongodb.default
    doctrine_couchdb.odm.default_document_manager => simplethings_tx.couchdb.default

You can mark actions as transactional by means of configuration. There are three different ways to configure the transactional behavior:

### Working with a default transaction manager

If you have a small RESTful application and you only use one transactional manager, for example the Doctrine ORM then your configuration
is as simple as configuring the transactional managers name in the app/config/config.yml extension configuration:

    simple_things_transactional:
        auto_transactional: orm.default

With this configuration every POST, PUT, DELETE and PATCH request is wrapped inside a transaction of the given name.
There is no way to disable this behavior except by throwing an exception. GET requests that need to write a transaction
have to do this explicitly.

### Working with explicit configuration

If you have an application that is either not RESTful, uses multiple transactional managers or has advanced
requirements with regard to transactions then you should configure the transactional behavior explicitly.

You can do so by specifying fcqn controller and action names either as regexp or as full key that is matched.
Every transactional configuration that matches for a given controller+action combination is started.

    simple_things_transactional:
        defaults:
            conn: ["mongodb.default"]
            methods: ["POST", "PUT", "DELETE", "PATCH"]
        patterns:
            fos_user:
                pattern: "FOS\(.*)Controller::(.*)Action"
                # not giving conn: uses the default
                propagation: REQUIRES_NEW
                noRollbackFor: ["NotFoundHttpException"]
            acme:
                pattern: "Acme(.*)"
                conn: ["orm.default", "couchdb.default"]
            acme_logging:
                pattern: "Acme\DemoBundle\Controller\IndexController::logAction"
                conn: ["dbal.other"]
                methods: ["GET"]

### Annotations

You can also configure transactional behavior with annotations. The configuration for annotations is as simple as:

    simple_things_transactional:
        annotations: true

The previous  `Acme\DemoBundle\Controller\IndexController` then looks like:

    namespace Acme\DemoBundle\Controller;

    use SimpleThings\TransactionalBundle\Annotations AS Tx;

    /**
     * @Tx\Transactional(conn={"orm.default", "couchdb.default"})
     */
    class IndexController
    {
        /**
         * @Tx\Transactional(conn={"orm.other"}, methods: {"GET"})
         */
        public function demoAction()
        {

        }
    }

## Example

Using the previous routes as example here is a sample action that does not require any calls to EntityManager::flush anymore.

    class PostController extends Controller
    {
        public function editAction($id)
        {
            $em = $this->container->get('doctrine.orm.default_entity_manager');
            $post = $em->find('Post', $id);
            
            if ($this->container->get('request')->getMethod() == 'POST') {
                $post->modifyState();
                // no need to call $em->flush(), the flush is executed in a transactional wrapper
            
                return $this->redirect($this->generateUrl("view_post", array("id" => $post->getId()));
            }

            return $this->render("MyBlogBundle:Post:edit.html.twig", array());
        }
    }

## Installation

1. Add TransactionalBundle to deps:

    [SimpleThingsTransactionalBundle]
    git=git@github.com:simplethings/SimpleThingsTransactionalBundle.git
    target=/bundles/SimpleThings/TransactionalBundle

2. Run ./bin/vendors to upgrade vendors and include TransactionalBundle

4. Add Bundle to app/AppKernel.php

    public function registerBundles() 
    {
        $bundles = array(
            //..
            new SimpleThings\TransactionalBundle\SimpleThingsTransactionalBundle(),
            //..
        );
    }

5. Add to autoload.php

    'SimpleThings'     => __DIR__.'/../vendor/bundles',

6. Configure extension:

    simple_things_transactional: ~

