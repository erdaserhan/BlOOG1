<?php

namespace model\Mapping;

use model\Abstract\AbstractMapping;
use DateTime;
use Exception;

class PermissionMapping extends AbstractMapping
{
    // Les propriétés de la classe sont le nom des
    // attributs de la table Exemple (qui serait en
    // base de données)
    protected ?int $permission_id;
    protected ?string $permission_name;
    protected ?string $permission_description;

    // Les getters et setters
    // Les getters permettent de récupérer la valeur
    // d'un attribut de la classe

    // Les setters permettent de modifier la valeur
    // d'un attribut de la classe, en utilisant l'hydratation
    // venant de la classe AbstractMapping
    public function getPermissionId(): ?int
    {
        return $this->permission_id;
    }

    public function setPermissionId(?int $permission_id): void
    {
        $this->permission_id = $permission_id;
    }

    public function getPermissionName(): ?string
    {
        return $this->permission_name;
    }

    public function setPermissionName(?string $permission_name): void
    {
        // la protection se fait dans les setters
        $this->permission_name = htmlspecialchars(strip_tags(trim($permission_name)),ENT_QUOTES);
    }

    public function getPermissionDescription(): ?string
    {

        return $this->permission_description;
    }

    public function setPermissionDescription(?string $permission_description): void
    {
        $this->permission_description = htmlspecialchars(strip_tags(trim($permission_description)),ENT_QUOTES);
    }

}
