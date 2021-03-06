<?php


namespace model\categories;


use model\Connection;

class CategoryDAO {
    public function createCategory(Category $category) {
        $parameters = [];
        $parameters[] = $category->getName();
        $parameters[] = $category->getType();
        $parameters[] = $category->getIcon();
        $parameters[] = $category->getOwnerId();

        $conn = Connection::getInstance()->getConn();
        $sql = "INSERT INTO transaction_categories(name, type, icon, owner_id)
                VALUES (?, ?, ?, ?);";
        $stmt = $conn->prepare($sql);
        $stmt->execute($parameters);
        $category->setId($conn->lastInsertId());
    }

    public function getAll($owner_id) {
        $conn = Connection::getInstance()->getConn();
        $sql = "SELECT id, name, type, icon, owner_id FROM transaction_categories 
                WHERE (owner_id is NULL OR owner_id = ? ) AND type IS NOT NULL ;";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$owner_id]);

        $categories = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $value) {
            $category = new Category($value->name, $value->type, $value->icon, $value->owner_id);
            $category->setId($value->id);
            $categories[] = $category;
        }
        return $categories;
    }

    public function editCategory(Category $category) {
        $conn = Connection::getInstance()->getConn();
        $sql = "UPDATE transaction_categories SET name = ?, icon = ? 
                WHERE id = ? AND owner_id = ?;";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $category->getName(),
            $category->getIcon(),
            $category->getId(),
            $category->getOwnerId()
        ]);
    }

    public function getCategoryById($category_id, $owner_id) {
        $conn = Connection::getInstance()->getConn();
        $sql = "SELECT id, name, type, icon, owner_id 
                FROM transaction_categories 
                WHERE id = ? AND (owner_id = ? OR owner_id IS NULL);";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$category_id, $owner_id]);
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            $category = new Category($row->name, $row->type, $row->icon, $row->owner_id);
            $category->setId($row->id);
            return $category;
        }
        return false;
    }

    public function deleteCategory(Category $category) {
        $conn = Connection::getInstance()->getConn();
        $sql = "DELETE FROM transaction_categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$category->getId()]);
    }
}