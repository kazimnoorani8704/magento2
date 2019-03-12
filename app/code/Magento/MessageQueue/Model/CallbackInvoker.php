<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MessageQueue\Model;

use Magento\Framework\MessageQueue\CallbackInvokerInterface;
use Magento\Framework\MessageQueue\QueueInterface;
use Magento\MessageQueue\Api\Data\PoisonPillInterface;
use Magento\MessageQueue\Api\PoisonPillCompareInterface;
use Magento\MessageQueue\Api\PoisonPillReadInterface;

/**
 * Callback invoker
 */
class CallbackInvoker implements CallbackInvokerInterface
{
    /**
     * @var PoisonPillReadInterface $poisonPillRead
     */
    private $poisonPillRead;

    /**
     * @var PoisonPillInterface $poisonPill
     */
    private $poisonPill;

    /**
     * @var PoisonPillCompareInterface
     */
    private $poisonPillCompare;

    /**
     * @param PoisonPillReadInterface $poisonPillRead
     * @param PoisonPillCompareInterface $poisonPillCompare
     */
    public function __construct(
        PoisonPillReadInterface $poisonPillRead,
        PoisonPillCompareInterface $poisonPillCompare
    ) {
        $this->poisonPillRead = $poisonPillRead;
        $this->poisonPillCompare = $poisonPillCompare;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function invoke(QueueInterface $queue, $maxNumberOfMessages, $callback)
    {
        $this->poisonPill = $this->poisonPillRead->getLatest();
        for ($i = $maxNumberOfMessages; $i > 0; $i--) {
            do {
                $message = $queue->dequeue();
            } while ($message === null && (sleep(1) === 0));
            if (false === $this->poisonPillCompare->isLatest($this->poisonPill)) {
                $queue->reject($message);
                exit(0);
            }
            $callback($message);
        }
    }
}
