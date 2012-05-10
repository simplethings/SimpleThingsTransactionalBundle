# SimpleThings TransactionalBundle

Provides the missing transactions support for Symfony2 on the framework level. The bundle wraps calls to controllers into transactions for either database connections or object managers. The bundle is stricly meant to be run in an HTTP context, using the HTTP verbs for differentating between read/write and read-only transactions.

## Installation

See at the end of this document.

## Problems to solve

Symfony2 allows to nest controllers into each other in unlimited amounts. These controllers can all modify and save data. The Doctrine persistence solutions (ORM, MongoDB, CouchDB) use a transactional write-behind mechanism to flush changes in batches, best executed at the end of the master request. If each controller or model service handles transactions themselves then you probably overuse the flush operation, which can lead to inconsistencies and performance penalities.

Additionally your domain code should not be cluttered with transactional code when its not stricly needed.

Therefore transaction management should by seperated from your domain model, handled by the framework in a HTTP context.

## How it works

For every Doctrine DBAL connection, every EntityManager and every DocumentManager the Transactional Bundle creates a service that implements a transactions provider interface:

    interface TransactionProviderInterface
    {
        /**
         * @return TransactionStatus
         */
        function createTransaction(TransactionDefinition $def);
    }

With the transactional bundle the following workflow is applied to an action that is marked as transactional

1. Detect which Connection should wrap the to-be-excecuted action and if its read/write or read-only.
2. A transaction is started before the controller is called.
3. The action is called by Symfony
4. On successful response generation (status code < 400) the transaction is committed. This includes a call to EntityManager::flush or DocumentManager::flush in case of an orm, mongodb or couchdb "transaction".
5. On status-code >= 400 the transaction is rolled back.
6. If an exception is thrown the transaction is rolled back.

You can mark actions as transactional by means of configuration. There are three different ways to configure the transactional behavior:

### Architectural Details

1. There is only exactly one transaction per action. This bundle will not automatically handle transactions across multiple data-source as this is a very implementation specific problem. If you need multiple connections handle one transaction in your application then implement your own transaction provider that implements some kind of two-phase commit.
2. A form extension is provided that will mark a transaction as rollback only validation on the form fails.
3. Transactions are either read/write or read-only. A read-only transaction is rolled back at the end of the request no matter what. In the context of ObjectMAnagers this means the flush operation is NOT called. The read/write or read-only status is detected by matching against the HTTP Verbs. By default GET requests are read-only and PUT, POST, DELETE and PATCH are read/write transactions.
4. If the read-only/read-write status switches during a sub-request an exception is thrown. Modes cannot be mixed.

## Configuration

### Auto-Transactional Mode

If you have a RESTful application and you only use one transactional manager, for example the Doctrine ORM then your configuration
is as simple as configuring the transactional managers name in the app/config/config.yml extension configuration:

    simple_things_transactional:
        auto_transactional: true
        defaults:
            conn: "orm.default"

With this configuration every POST, PUT, DELETE and PATCH request is wrapped inside a transaction of the given connection.
There is no way to disable this behavior except by throwing an exception. GET requests that need to write a transaction
have to do this explicitly.

### Controller Pattern Matching

If you have an application that is either not RESTful, uses multiple transactional managers or has advanced
requirements with regard to transactions then you should configure the transactional behavior explicitly.

You can do so by matching fcqn controller and action names with regular expression.
Every transactional configuration that matches for a given controller+action combination is started.
If a transaction is started for a connection multiple times then an exception is thrown.

    simple_things_transactional:
        defaults:
            conn: "mongodb.default"
            methods: ["POST", "PUT", "DELETE", "PATCH"]
        patterns:
            fos_user:
                pattern: "FOS\(.*)Controller::(.*)Action"
                # not giving conn: uses the default
            acme:
                pattern: "Acme(.*)"
            acme_logging:
                pattern: "Acme\DemoBundle\Controller\IndexController::logAction"
                conn: "orm.other"
                methods: ["GET"]

### Annotations

You can also configure transactional behavior with annotations. Enabling annotations is simple:

    simple_things_transactional:
        annotations: true

The previous  `Acme\DemoBundle\Controller\IndexController` can then be configured by adding:

    namespace Acme\DemoBundle\Controller;

    use SimpleThings\TransactionalBundle\Transactions\Annotations AS Tx;

    /**
     * @Tx\Transactional(conn="orm.default")
     */
    class IndexController
    {
        public function indexAction()
        {
            // orm.default transaction here
        }

        /**
         * @Tx\Transactional(conn="orm.other", methods: {"GET"})
         */
        public function demoAction()
        {
            // orm.other transaction here
        }
    }

## Doctrine ORM Example

This example assumes:

* You are using FrameworkExtraBundle and converters
* You automatically use HTTP semantics for tx management.

See this controller for a Form Edit/Display.

* It will commit all the changes automatically when a post request occurs.
* To rollback the transaction when a form error occurs the transactional
  bundle automatically registers a form validator and sets all current
  transactions to 'rollback only'.

Here is the code:

    class PostController extends Controller
    {
        /**
         * @ParamConverter("post", class="AcmeBlogBundle:Post")
         * @Template
         */
        public function editAction(Post $post, Request $request)
        {
            $form = $this->createForm(new PostType(), $post);

            if ($request->getMethod() == 'POST') {
                $form->bindRequest($request);

                if ($form->isValid()) {
                    return $this->redirect($this->generateUrl("view_post", array("id" => $post->getId()));
                }
            }

            return array('form' => $form->createView());
        }
    }


## Installation

On Composer as 'simplethings/transactional-bundle' package.

Or oldschool:

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

# Todos

* Move configuration to Symfony\Component\Config
* Add modes 'autocommit', 'commit_on_success' (default) and 'manual' that determine how an action should be handled transactionally.

