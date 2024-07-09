<?php

namespace model\Manager;

use model\Mapping\ArticleMapping;
use model\OurPDO;

use model\Interface\InterfaceManager;
use model\Interface\InterfaceSlugManager;

use model\Mapping\UserMapping;
use model\Mapping\CategoryMapping;
use model\Mapping\TagMapping;

/**
 * Class ArticleManager
 *
 * Cette classe permet de gérer les articles en base de données.
 * Elle implémente les méthodes de l'interface InterfaceManager
 * et InterfaceSlugManager.
 *
 */
class ArticleManager implements InterfaceManager, InterfaceSlugManager
{

    // Attributes
    private OurPDO $db; // contient la connexion à la base de données

    public function __construct(OurPDO $pdo)
    {
        // on stocke la connexion à la base de données
        // dans l'attribut privé $db
        $this->db = $pdo;
    }

    // simple selectAll sur la table article
    public function selectAll($limit=999): ?array
    {
        // on récupère tous les articles
        $query = $this->db->query("SELECT * 
                                         FROM (SELECT * 
                                               FROM article 
                                               ORDER BY RAND() 
                                               LIMIT $limit) as randSelect 
                                         ORDER BY article_id");
        // si aucun article n'est trouvé, on retourne null
        if ($query->rowCount() == 0) return null;
        // on récupère les articles sous forme de tableau associatif
        $tabMapping = $query->fetchAll();
        // on ferme le curseur
        $query->closeCursor();
        // on crée le tableau où on va instancier les objets
        $tabObject = [];
        foreach ($tabMapping as $mapping) {
            $tabObject[] = new ArticleMapping($mapping);

        }
        return $tabObject;
    }

    public function selectAllArticleHomepage($limit=999): ?array
    {

        // on récupère tous les articles avec jointures
        // J'ai ajouté les tags dedans aussi
        $query = $this->db->query("
        SELECT * FROM(
        SELECT a.article_id, a.article_title, 
               SUBSTRING_INDEX(a.article_text,' ', 30) as article_text,
               a.article_slug, a.article_date_publish, 
               u.user_id, u.user_login, u.user_full_name,
               GROUP_CONCAT(c.category_id) as category_id, 
               GROUP_CONCAT(c.category_name SEPARATOR '|||') as category_name, 
               GROUP_CONCAT(c.category_slug SEPARATOR '|||') as category_slug,
               GROUP_CONCAT(tag.tag_id SEPARATOR '|||') as tag_id,
               GROUP_CONCAT(tag.tag_slug SEPARATOR '|||') as tag_slug,
               (SELECT COUNT(*)
                    FROM comment c
                    WHERE a.article_id = c.article_article_id)
                   as comment_count
        FROM article a
        INNER JOIN user u  
            ON u.user_id = a.user_user_id
        LEFT JOIN article_has_category ahc
            ON ahc.article_article_id = a.article_id
        LEFT JOIN category c
            ON c.category_id = ahc.category_category_id
         JOIN tag_has_article tha
        	ON tha.article_article_id = a.article_id
         JOIN tag 
        	ON tag.tag_id = tha.tag_tag_id
        WHERE a.article_is_published = 1
            GROUP BY a.article_id
            ORDER BY a.article_date_publish DESC
) as mainSel
            ORDER BY RAND() LIMIT $limit
        
        ");
        // si aucun article n'est trouvé, on retourne null
        if ($query->rowCount() == 0) return null;
        // on récupère les articles sous forme de tableau associatif
        $tabMapping = $query->fetchAll();
        // on ferme le curseur
        $query->closeCursor();
        // on crée le tableau où on va instancier les objets
        $tabObject = [];
        // pour chaque article, on boucle
        foreach ($tabMapping as $mapping) {
            // si on a un user on l'instancie
            $user = $mapping['user_login'] !== null ? new UserMapping($mapping) : null;
            // si on a des catégories
            if ($mapping['category_id'] !== null) {
                // on crée un tableau de catégories
                $tabCategories = [];
                // on récupère les catégories
                $tabCategoryIds = explode(",", $mapping['category_id']);
                $tabCategoryNames = explode("|||", $mapping['category_name']);
                $tabCategorySlugs = explode("|||", $mapping['category_slug']);
                // on boucle sur les catégories
                for ($i = 0; $i < count($tabCategoryIds); $i++) {
                    // on instancie la catégorie
                    $category = new CategoryMapping([
                        'category_id' => $tabCategoryIds[$i],
                        'category_name' => $tabCategoryNames[$i],
                        'category_slug' => $tabCategorySlugs[$i]
                    ]);
                    // on ajoute la catégorie au tableau
                    $tabCategories[] = $category;
                }

            } else {
                $tabCategories = null;
            }
            if ($mapping["tag_slug"] !== null){
                $tabTags = [];
                $tabTagIds = explode("|||", $mapping["tag_id"]);
                $tabTagSlugs = explode("|||", $mapping["tag_slug"]);
                for ($i = 0; $i < count($tabTagSlugs); $i++) {
                    $tags = new TagMapping([
                        'tag_id' => $tabTagIds[$i],
                        'tag_slug' => $tabTagSlugs[$i]
                    ]);
                    $tabTags[] = $tags;
                }
            }else {
                $tabTags = null;
            }

            // on instancie l'article
            $article = new ArticleMapping($mapping);
            // on ajoute user à l'article
            $article->setUser($user);
            // on ajoute les catégories à l'article
            $article->setCategories($tabCategories);
            // on ajoute l'article au tableau
            $article->setTags($tabTags);
            // die(var_dump($article)); // une heure perdu car il n'affiché pas les cats et tags....par contre avec die() on les vois
            $tabObject[] = $article;
        }
        return $tabObject;
    }

    public function selectAllArticleByCategorySlug(string $slug): ?array
    {

        // on récupère tous les articles avec jointures
        $prepare = $this->db->prepare("
        SELECT a.`article_id`, a.`article_title`, 
               SUBSTRING_INDEX(a.`article_text`,' ', 30) as `article_text`,
               a.`article_slug`, a.`article_date_publish`, 
               u.`user_id`, u.`user_login`, u.`user_full_name`,
               
               (SELECT COUNT(*)
                    FROM `comment` c
                    WHERE a.`article_id` = c.`article_article_id`)
                   as `comment_count`
        FROM `article` a
        INNER JOIN `user` u  
            ON u.`user_id` = a.`user_user_id`
        LEFT JOIN article_has_category ahc
            ON ahc.`article_article_id` = a.`article_id`
        LEFT JOIN category c
            ON c.`category_id` = ahc.`category_category_id`
        WHERE a.`article_is_published` = 1
        AND c.`category_slug` = :slug
            GROUP BY a.`article_id`
            ORDER BY a.`article_date_publish` DESC
        
        ");
        $prepare->execute(['slug' => $slug]);
        // si aucun article n'est trouvé, on retourne null
        if ($prepare->rowCount() == 0) return null;
        // on récupère les articles sous forme de tableau associatif
        $tabMapping = $prepare->fetchAll();
        // on ferme le curseur
        $prepare->closeCursor();
        // on crée le tableau où on va instancier les objets
        $tabObject = [];
        // pour chaque article, on boucle
        foreach ($tabMapping as $mapping) {
            // si on a un user on l'instancie
            $user = $mapping['user_login'] !== null ? new UserMapping($mapping) : null;

            // on instancie l'article
            $article = new ArticleMapping($mapping);
            // on ajoute user à l'article
            $article->setUser($user);
            // on ajoute l'article au tableau
            $tabObject[] = $article;
        }
        return $tabObject;
    }


    public function selectOneById(int $id): object
    {
        // TODO: Implement selectOneById() method.
    }

    public function insert(object $object): void
    {
        // TODO: Implement insert() method.
    }

    public function update(object $object): void
    {
        // TODO: Implement update() method.
    }

    public function delete(int $id): void
    {
        // TODO: Implement delete() method.
    }

    public function selectOneBySlug(string $slug)
    {
        // on récupère tous les articles avec jointures
        $query = $this->db->prepare("
        SELECT a.*, 
       u.`user_id`, u.`user_login`, u.`user_full_name`,
       GROUP_CONCAT(c.`category_id`) as `category_id`, 
       GROUP_CONCAT(c.`category_name` SEPARATOR '|||') as `category_name`, 
       GROUP_CONCAT(c.`category_slug` SEPARATOR '|||') as `category_slug`,
       (SELECT GROUP_CONCAT(t.`tag_slug` SEPARATOR '|||')
            FROM `tag` t
            INNER JOIN `tag_has_article` tha
                ON tha.`article_article_id` = a.`article_id`
            WHERE t.`tag_id` = tha.`tag_tag_id`
            ORDER BY t.`tag_slug` ASC
        ) as `tag_slug`,
       (SELECT GROUP_CONCAT(t.`tag_id`)
            FROM `tag` t
            INNER JOIN `tag_has_article` tha
                ON tha.`article_article_id` = a.`article_id`
            WHERE t.`tag_id` = tha.`tag_tag_id`
        ) as `tag_id`
FROM `article` a
INNER JOIN `user` u  
    ON u.`user_id` = a.`user_user_id`
LEFT JOIN article_has_category ahc
    ON ahc.`article_article_id` = a.`article_id`
LEFT JOIN category c
    ON c.`category_id` = ahc.`category_category_id`
WHERE a.`article_is_published` = 1
    AND a.`article_slug` = :slug
GROUP BY a.`article_id`

        
        ");
        $query->execute(['slug' => $slug]);
        // si aucun article n'est trouvé, on retourne null
        if ($query->rowCount() == 0) return null;
        // on récupère les articles sous forme de tableau associatif
        $mapping = $query->fetch();
        // on ferme le curseur
        $query->closeCursor();

            // si on a un user on l'instancie
            $user = $mapping['user_login'] !== null ? new UserMapping($mapping) : null;
            // si on a des catégories
            if ($mapping['category_id'] !== null) {
                // on crée un tableau de catégories
                $tabCategories = [];
                // on récupère les catégories
                $tabCategoryIds = explode(",", $mapping['category_id']);
                $tabCategoryNames = explode("|||", $mapping['category_name']);
                $tabCategorySlugs = explode("|||", $mapping['category_slug']);
                // on boucle sur les catégories
                for ($i = 0; $i < count($tabCategoryIds); $i++) {
                    // on instancie la catégorie
                    $category = new CategoryMapping([
                        'category_id' => $tabCategoryIds[$i],
                        'category_name' => $tabCategoryNames[$i],
                        'category_slug' => $tabCategorySlugs[$i]
                    ]);
                    // on ajoute la catégorie au tableau
                    $tabCategories[] = $category;
                }

            } else {
                $tabCategories = null;
            }
            // si on a des tags
            if ($mapping['tag_slug'] !== null) {
                // on crée un tableau de tags
                $tabTags = [];
                // on récupère les tags
                $tabTagSlugs = explode("|||", $mapping['tag_slug']);
                $tabTagIds = explode(",", $mapping['tag_id']);
                // on boucle sur les tags
                for ($i = 0; $i < count($tabTagSlugs); $i++) {
                    // on instancie le tag
                    $tag = new TagMapping([
                        'tag_slug' => $tabTagSlugs[$i],
                        'tag_id' => $tabTagIds[$i]
                    ]);
                    // on ajoute le tag au tableau
                    $tabTags[] = $tag;
                }
            } else {
                $tabTags = null;
            }


            // on instancie l'article
            $article = new ArticleMapping($mapping);
            // on ajoute user à l'article
            $article->setUser($user);
            // on ajoute les catégories à l'article
            $article->setCategories($tabCategories);
            // on ajoute les tags à l'article
            $article->setTags($tabTags);
            // on retourne l'article

        return $article;
    }

    public function selectArticlesByUser(int $userId): ?array {
        $prep = $this->db->prepare("SELECT *
                                          FROM article
                                          WHERE user_user_id
                                          = :userId");
        $prep->execute(['userId' => $userId]);
        if ($prep->rowCount() == 0) return null;
        $mapping = $prep->fetchAll(OurPDO::FETCH_ASSOC);
        $prep->closeCursor();

            $tabObject = [];
        foreach ($mapping as $map) {
            $tabObject[] = new ArticleMapping($map);

        }
        return $tabObject;
    }

    public function selectArticlesForOneUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("
       SELECT 
           u.user_id,
           u.user_full_name,
           a.article_id,
           a.article_title,
           a.article_date_publish,
           SUBSTR(a.article_text, 1, 75) as article_text
       FROM user u
       LEFT JOIN article a ON a.user_user_id = u.user_id
       WHERE u.user_id = :userId AND a.article_is_published = 1
       ORDER BY a.article_date_publish DESC;
    ");

        $stmt->execute(['userId' => $userId]);
        $articles = $stmt->fetchAll(OurPDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (empty($articles)) return null;

        $author = [
            'user_id' => $articles[0]['user_id'],
            'user_full_name' => $articles[0]['user_full_name'],
            'articles' => []
        ];

        foreach ($articles as $article) {
            $author['articles'][] = new ArticleMapping($article);
        }
        return $author;
    }

}

