<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mappings
 *
 * @ORM\Table(name="mappings", indexes={@ORM\Index(name="media_type", columns={"media_type"}), @ORM\Index(name="client", columns={"client"})})
 * @ORM\Entity
 */
class Mapping
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="rfid", type="string", length=255, nullable=false)
     */
    private $rfid;

    /**
     * @var string
     *
     * @ORM\Column(name="additional_information", type="string", length=255, nullable=false)
     */
    private $additionalInformation;

    /**
     * @var ?string
     *
     * @ORM\Column(name="local_path", type="string", length=255, nullable=true)
     */
    private $localPath;

    /**
     * @var string
     *
     * @ORM\Column(name="lms_path", type="string", length=255, nullable=false)
     */
    private $lmsPath;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Client")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="client", referencedColumnName="id")
     * })
     */
    private $client;

    /**
     * @var MediaType
     *
     * @ORM\ManyToOne(targetEntity="MediaType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="media_type", referencedColumnName="id")
     * })
     */
    private $mediaType;

    /**
     * @return int
     */
    public function getId():? int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * 
     * @return Mapping
     */
    public function setId(int $id): Mapping
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getRfid():? string
    {
        return $this->rfid;
    }

    /**
     * @param string $rfid
     * 
     * @return Mapping
     */
    public function setRfid(string $rfid): Mapping
    {
        $this->rfid = $rfid;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalInformation():? string
    {
        return $this->additionalInformation;
    }

    /**
     * @param string $additionalInformation
     * 
     * @return Mapping
     */
    public function setAdditionalInformation(string $additionalInformation): Mapping
    {
        $this->additionalInformation = $additionalInformation;
        return $this;
    }

    /**
     * @return ?string
     */
    public function getLocalPath(): ?string
    {
        return $this->localPath;
    }

    /**
     * @param ?string $localPath
     * 
     * @return Mapping
     */
    public function setLocalPath(?string $localPath): Mapping
    {
        $this->localPath = $localPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getLmsPath():? string
    {
        return $this->lmsPath;
    }

    /**
     * @param string $lmsPath
     * 
     * @return Mapping
     */
    public function setLmsPath(string $lmsPath): Mapping
    {
        $this->lmsPath = $lmsPath;
        return $this;
    }

    /**
     * @return Client
     */
    public function getClient():? Client
    {
        return $this->client;
    }

    /**
     * @param Client $client
     * 
     * @return Mapping
     */
    public function setClient(Client $client): Mapping
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return MediaType
     */
    public function getMediaType():? MediaType
    {
        return $this->mediaType;
    }

    /**
     * @param MediaType $mediaType
     * 
     * @return Mapping
     */
    public function setMediaType(MediaType $mediaType): Mapping
    {
        $this->mediaType = $mediaType;
        return $this;
    }

}
