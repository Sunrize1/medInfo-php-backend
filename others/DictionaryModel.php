<?php
class DictionaryModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSpecialtiesList($name, $offset, $size) {
    $sql = "
        SELECT 
            id, 
            createtime, 
            name 
        FROM 
            speciality 
        WHERE 
            name LIKE :name 
        LIMIT 
            :size 
        OFFSET 
            :offset
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        'name' => '%' . $name . '%',
        'size' => $size,
        'offset' => $offset
    ]);
    return $stmt->fetchAll();

    }

    public function getSpecialtiesCount($name) {
        $sql = "
        SELECT 
            COUNT(*) AS total 
        FROM 
            speciality 
        WHERE 
            name LIKE :name
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['name' => '%' . $name . '%']);
    return $stmt->fetch()['total'];
    }

    public function getIcd10Count($request) {
        $sql = "
        SELECT 
            COUNT(*) AS total 
        FROM 
            icd_10 
        WHERE 
            name LIKE :request OR code LIKE :request
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['request' => '%' . $request . '%']);
    return $stmt->fetch()['total'];
    }

    public function getIcd10List($request, $offset, $size) {
        $sql = "
        SELECT 
            id, 
            code,
            name 
        FROM 
            icd_10 
        WHERE 
            name LIKE :request OR code LIKE :request 
        LIMIT 
            :size 
        OFFSET 
            :offset 
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
        'request' => '%' . $request . '%',
        'size' => $size,
        'offset' => $offset]);
        return $stmt->fetchAll();
    }

    public function getIcd10Roots() {
        $sql = "
        SELECT 
            id, 
            code,
            name 
        FROM 
            icd_10 
        WHERE 
            parent_id_in_icd_10 IS NULL 
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
