<?php
namespace SimplyTestable\WorkerBundle\Entity\Task\Type;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="TaskType")
  */
class Type
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     *
     * @var string
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    protected $description;

    /**
     * @var TaskTypeClass
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\WorkerBundle\Entity\Task\Type\TaskTypeClass")
     * @ORM\JoinColumn(name="tasktypeclass_id", referencedColumnName="id", nullable=false)
     */
    protected $class;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="selectable", nullable=false)
     */
    protected $selectable = false;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param TaskTypeClass $class
     *
     * @return $this
     */
    public function setClass(TaskTypeClass $class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return TaskTypeClass
     */
    public function getClass()
    {
        return $this->class;
    }


    public function __construct()
    {
        $this->selectable = false;
    }

    /**
     * @param boolean $selectable
     *
     * @return $this
     */
    public function setSelectable($selectable)
    {
        $this->selectable = $selectable;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getSelectable()
    {
        return $this->selectable;
    }

    /**
     * @param Type $taskType
     *
     * @return boolean
     */
    public function equals(Type $taskType)
    {
        return $this->getName() == $taskType->getName();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
