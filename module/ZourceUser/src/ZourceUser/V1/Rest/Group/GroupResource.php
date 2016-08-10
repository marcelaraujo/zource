<?php
/**
 * This file is part of Zource. (https://github.com/zource/)
 *
 * @link https://github.com/zource/zource for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zource. (https://github.com/zource/)
 * @license https://raw.githubusercontent.com/zource/zource/master/LICENSE MIT
 */

namespace ZourceUser\V1\Rest\Group;

use Doctrine\ORM\EntityManager;
use DoctrineModule\Paginator\Adapter\Selectable;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZF\Rest\AbstractResourceListener;
use ZourceUser\Entity\Group;

class GroupResource extends AbstractResourceListener
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create($data)
    {
        $group = new Group($data->name);

        $this->entityManager->persist($group);
        $this->entityManager->flush($group);

        return new GroupEntity($group);
    }

    public function fetch($id)
    {
        /** @var Group $group */
        $group = $this->entityManager->find(Group::class, $id);
        if (!$group) {
            return null;
        }

        return new GroupEntity($group);
    }

    public function fetchAll($params = [])
    {
        $repository = $this->entityManager->getRepository(Group::class);

        $adapter = new Selectable($repository);

        return new GroupCollection($adapter);
    }

    public function patch($id, $data)
    {
        return parent::patch($id, $data); // TODO: Change the autogenerated stub
    }

    public function update($id, $data)
    {
        /** @var Group $group */
        $group = $this->entityManager->find(Group::class, $id);

        if (!$group) {
            return new ApiProblem(ApiProblemResponse::STATUS_CODE_404, 'Entity not found.');
        }

        $group->setName($data->name);

        if (isset($data->description)) {
            $group->setDescription($data->description);
        }

        $this->entityManager->flush($group);

        return new GroupEntity($group);
    }
}
