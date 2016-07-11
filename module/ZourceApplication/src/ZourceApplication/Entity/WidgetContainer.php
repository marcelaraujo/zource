<?php
/**
 * This file is part of Zource. (https://github.com/zource/)
 *
 * @link https://github.com/zource/zource for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zource. (https://github.com/zource/)
 * @license https://raw.githubusercontent.com/zource/zource/master/LICENSE MIT
 */

namespace ZourceApplication\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WidgetContainer
{
    const LAYOUT_33_33_34 = '33-33-34';
    const LAYOUT_33_67 = '33-67';
    const LAYOUT_50_50 = '50-50';
    const LAYOUT_67_33 = '67-33';
    const LAYOUT_100 = '100';

    /**
     * @var UuidInterface
     */
    private $id;

    /**
     * @var string
     */
    private $layout;

    /**
     * @var Collection
     */
    private $widgets;

    /**
     * Initializes a new instance of this class.
     *
     * @param string $layout
     */
    public function __construct($layout)
    {
        $this->id = Uuid::uuid4();
        $this->layout = $layout;
        $this->widgets = new ArrayCollection();
    }

    /**
     * @return UuidInterface
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->updateWidgets($this->layout, $layout);

        $this->layout = $layout;
    }

    /**
     * @return Collection
     */
    public function getWidgets()
    {
        return $this->widgets;
    }

    public function getColumns()
    {
        return explode('-', $this->getLayout());
    }

    public function getWidgetsForColumn($column)
    {
        $result = [];

        foreach ($this->getWidgets() as $widget) {
            if ($widget->getColumn() === $column) {
                $result[] = $widget;
            }
        }

        return $result;
    }

    private function updateWidgets($oldLayout, $newLayout)
    {
        // TODO
    }
}
