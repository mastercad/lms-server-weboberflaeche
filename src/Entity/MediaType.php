<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MediaTypes
 *
 * @ORM\Table(name="media_types", uniqueConstraints={@ORM\UniqueConstraint(name="ux_name", columns={"name"})})
 * @ORM\Entity
 */
class MediaType
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @return int
     */
    public function getId():? int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return MediaType
     */
    public function setId(int $id): MediaType
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName():? string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return MediaType
     */
    public function setName(string $name): MediaType
    {
        $this->name = $name;
        return $this;
    }

}
