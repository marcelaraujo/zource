<?php
/**
 * This file is part of Zource. (https://github.com/zource/)
 *
 * @link https://github.com/zource/zource for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zource. (https://github.com/zource/)
 * @license https://raw.githubusercontent.com/zource/zource/master/LICENSE MIT
 */

namespace ZourceUser\V1\Rest\Identity;

use ZourceApplication\Rest\AbstractDoctrineListener;
use ZourceUser\Entity\AccountInterface;

class IdentityResource extends AbstractDoctrineListener
{
    /**
     * {@inheritDoc}
     */
    public function create($data)
    {
        $account = $this->getEntityManager()->getRepository(AccountInterface::class)->find($data->account);

        $data->account = $account;

        return parent::create($data);
    }
}
