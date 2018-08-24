<?php

/**
 * This file is part of the TchoulomViewCounterBundle package.
 *
 * @package    TchoulomViewCounterBundle
 * @author     Original Author <tchoulomernest@yahoo.fr>
 *
 * (c) Ernest TCHOULOM <https://www.tchoulom.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tchoulom\ViewCounterBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Tchoulom\ViewCounterBundle\Entity\ViewCounterInterface;
use Tchoulom\ViewCounterBundle\Model\ViewCountable;
use Tchoulom\ViewCounterBundle\Persister\PersisterInterface;
use Tchoulom\ViewCounterBundle\Util\Date;

/**
 * Class AbstractViewCounter
 */
abstract class AbstractViewCounter
{
    /**
     * @var PersisterInterface
     */
    protected $persister;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * The View interval.
     * @var array
     */
    protected $viewInterval;

    /**
     * The viewCounter
     */
    protected $viewCounter = null;

    /**
     * The class namespace
     */
    protected $class = null;

    /**
     * The property
     */
    protected $property = null;

    /**
     * ViewCounter constructor.
     *
     * @param PersisterInterface $persister
     * @param RequestStack $requestStack
     */
    public function __construct(PersisterInterface $persister, RequestStack $requestStack, array $viewInterval)
    {
        $this->persister = $persister;
        $this->requestStack = $requestStack;
        $this->viewInterval = $viewInterval;
    }

    /**
     * Determines whether the view is new.
     *
     * @param ViewCounterInterface $viewCounter
     *
     * @return mixed
     */
    abstract protected function isNewView(ViewCounterInterface $viewCounter);

    /**
     * Loads the ViewCounter.
     *
     * @param ViewCountable $page The counted object(a tutorial or course...)
     */
    public function loadViewCounter(ViewCountable $page)
    {
        $metadata = $this->persister->loadMetadata($page);
        $this->property = $metadata->getAssociationMappings()['viewCounters']['mappedBy'];
        $this->class = $metadata->getAssociationMappings()['viewCounters']['targetEntity'];

        $this->viewCounter = $this->persister->findOneBy($this->class, $criteria = [$this->property => $page, 'ip' => $this->getRequest()->getClientIp()], $orderBy = null, $limit = null, $offset = null);
    }

    /**
     * Gets the ViewCounter.
     *
     * @param null $page The counted object(a tutorial or course...)
     *
     * @return null|\Tchoulom\ViewCounterBundle\Entity\ViewCounter
     */
    public function getViewCounter($page = null)
    {
        if (null == $this->viewCounter) {
            $this->loadViewCounter($page);
        }

        return $this->viewCounter;
    }

    /**
     * Gets the page Views.
     *
     * @param ViewCountable $page The counted object(a tutorial or course...)
     *
     * @return int
     */
    public function getViews(ViewCountable $page)
    {
        $viewCounter = $this->getViewCounter($page);

        if (null == $viewCounter || $this->isNewView($viewCounter)) {
            return $page->getViews() + 1;
        }

        return $page->getViews();
    }

    /**
     * Saves the View
     *
     * @param ViewCountable $page
     *
     * @return ViewCountable
     */
    public function saveView(ViewCountable $page)
    {
        $views = $this->getViews($page);
        $viewcounter = $this->getViewCounter($page);
        $viewCounterObject = $this->createViewCounterObject();

        $viewcounter = (null != $viewcounter ? $viewcounter : $viewCounterObject);

        if ($this->isNewView($viewcounter)) {
            $viewcounter->setIp($this->getRequest()->getClientIp());
            $setPage = 'set' . ucfirst($this->getProperty());
            $viewcounter->$setPage($page);
            $viewcounter->setViewDate($this->getNowDate());

            $page->setViews($views);

            $this->persister->save($viewcounter);
            $this->persister->save($page);
        }

        return $views;
    }

    /**
     * Gets the current Request.
     *
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * Gets view interval name.
     *
     * @return int|string
     */
    public function getViewIntervalName()
    {
        $viewIntervalName = '';
        foreach ($this->viewInterval[0] as $key => $vi) {
            $viewIntervalName = $key;
            break;
        }

        return $viewIntervalName;
    }

    /**
     * Creates the ViewCounter Object from the class namespace.
     *
     * @return mixed
     */
    public function createViewCounterObject()
    {
        $class = $this->getClass();
        $viewCounterObject = new $class();

        return $viewCounterObject;
    }

    /**
     * Gets the class namespace.
     *
     * @return null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Gets the property.
     *
     * @return null
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Gets now Date.
     *
     * @return \DateTime
     */
    protected function getNowDate()
    {
        return Date::getNowDate();
    }
}