<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Project
 *
 * @ORM\Table(name="project", indexes={@ORM\Index(name="fk_project_1_idx", columns={"user_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\ProjectRepository")
 */
class Project implements \JsonSerializable
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
     * @ORM\Column(name="name", type="string", length=45, nullable=false)
     */
    public $name;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    public $user;

    public function getUser()
    {
        return $this->user;
    }

    public function JsonSerialize(): array
    {
        return ["id" => $this->id, "name" => $this->name, "user" => $this->getUser()];
    }
}
