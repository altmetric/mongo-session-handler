<?php
namespace Altmetric;

class MongoSessionHandler implements \SessionHandlerInterface
{
    private $collection;
    private $logger;

    public function __construct(\MongoCollection $collection, \Psr\Log\LoggerInterface $logger)
    {
        $this->collection = $collection;
        $this->logger = $logger;
    }

    public function open($_save_path, $_name)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        $this->logger->debug("Reading session {$id}");

        $session = $this->collection->findOne(['_id' => $id], ['data' => 1]);

        if ($session) {
            $this->logger->debug("Session {$id} found, returning data");

            return $session['data']->bin;
        } else {
            $this->logger->debug("No session {$id} found, returning no data");

            return '';
        }
    }

    public function write($id, $data)
    {
        $session = [
            '_id' => $id,
            'data' => new \MongoBinData($data, \MongoBinData::BYTE_ARRAY),
            'last_accessed' => new \MongoDate()
        ];

        try {
            $this->logger->debug("Saving data {$data} to session {$id}");
            $this->collection->save($session);

            return true;
        } catch (\MongoException $e) {
            $this->logger->error("Error when saving {$data} to session {$id}: {$e->getMessage()}");

            return false;
        }
    }

    public function destroy($id)
    {
        $this->logger->debug("Destroying session {$id}");

        try {
            $this->collection->remove(['_id' => $id]);

            return true;
        } catch (\MongoException $e) {
            $this->logger->error("Error removing session {$id}: {$e->getMessage()}");

            return false;
        }
    }

    public function gc($maxlifetime)
    {
        $lastAccessed = new \MongoDate(time() - $maxlifetime);

        try {
            $this->logger->debug("Removing any sessions older than {$lastAccessed}");
            $this->collection->remove(['last_accessed' => ['$lt' => $lastAccessed]]);

            return true;
        } catch (\MongoException $e) {
            $this->logger->error("Error removing sessions older than {$lastAccessed}: {$e->getMessage()}");

            return false;
        }
    }
}
