<?php
// src/Clifton/ClothesBuilderBundle/Entity/ClientShop.php
namespace Clifton\ClothesBuilderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="client_shop")
 * @ORM\HasLifecycleCallbacks()
 */
class ClientShop extends MetaTags
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $title;
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $slug;
    /**
     * @ORM\OneToOne(targetEntity="Clifton\ClothesBuilderBundle\Entity\Slider")
     * @ORM\JoinColumn(name="slider", referencedColumnName="id")
     **/
    private $slider;
    /**
     * @ORM\OneToMany(targetEntity="Categories", mappedBy="shop", cascade={"all"})
     */
    private $categories;
    /**
     * @ORM\Column(type="boolean")
     */
    private $publish = false;
    /**
     * @ORM\Column(type="date")
     */
    private $open_date;
    /**
     * @ORM\Column(type="date")
     */
    private $close_date;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return ClientShop
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return ClientShop
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set publish
     *
     * @param boolean $publish
     *
     * @return ClientShop
     */
    public function setPublish($publish)
    {
        $this->publish = $publish;

        return $this;
    }

    /**
     * Get publish
     *
     * @return boolean
     */
    public function getPublish()
    {
        return $this->publish;
    }

    /**
     * Set slider
     *
     * @param \Clifton\ClothesBuilderBundle\Entity\Slider $slider
     *
     * @return ClientShop
     */
    public function setSlider(\Clifton\ClothesBuilderBundle\Entity\Slider $slider = null)
    {
        $this->slider = $slider;

        return $this;
    }

    /**
     * Get slider
     *
     * @return \Clifton\ClothesBuilderBundle\Entity\Slider
     */
    public function getSlider()
    {
        return $this->slider;
    }

    /**
     * Add category
     *
     * @param \Clifton\ClothesBuilderBundle\Entity\Categories $category
     *
     * @return ClientShop
     */
    public function addCategory(\Clifton\ClothesBuilderBundle\Entity\Categories $category)
    {
        $category->setShop($this);

        $this->categories[] = $category;

        return $this;
    }

    /**
     * Remove category
     *
     * @param \Clifton\ClothesBuilderBundle\Entity\Categories $category
     */
    public function removeCategory(\Clifton\ClothesBuilderBundle\Entity\Categories $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Set openDate
     *
     * @param \DateTime $openDate
     *
     * @return ClientShop
     */
    public function setOpenDate($openDate)
    {
        $this->open_date = $openDate;

        return $this;
    }

    /**
     * Get openDate
     *
     * @return \DateTime
     */
    public function getOpenDate()
    {
        return $this->open_date;
    }

    /**
     * Set closeDate
     *
     * @param \DateTime $closeDate
     *
     * @return ClientShop
     */
    public function setCloseDate($closeDate)
    {
        $this->close_date = $closeDate;

        return $this;
    }

    /**
     * Get closeDate
     *
     * @return \DateTime
     */
    public function getCloseDate()
    {
        return $this->close_date;
    }
}
