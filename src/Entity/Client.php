<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Client
 *
 * @ORM\Table(name="clients", uniqueConstraints={@ORM\UniqueConstraint(name="name", columns={"name"})})
 * @ORM\Entity
 */
class Client implements \JsonSerializable
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
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=15, nullable=false)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="mac_address", type="string", length=50, nullable=false)
     */
    private $macAddress;

    /**
     * @return int
     */
    public function getId():? int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Client
     */
    public function setId(int $id): Client
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
     * @return Client
     */
    public function setName(string $name): Client
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp():? string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return Client
     */
    public function setIp(string $ip): Client
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @return string
     */
    public function getMacAddress():? string
    {
        return $this->macAddress;
    }

    /**
     * @param string $macAddress
     * @return Client
     */
    public function setMacAddress(string $macAddress): Client
    {
        $this->macAddress = $macAddress;
        return $this;
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('ip', new Assert\Ip());
    }

    public function jsonSerialize(): array
    {
        return [
            'id'           => $this->getId(),
            'name'         => $this->getName(),
            'ip'           => $this->getIp(),
            'mac'          => $this->getMacAddress(),
        ];
    }
}
