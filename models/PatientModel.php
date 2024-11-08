<?php
class PatientModel {
    private $pdo;


    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    

    public function create($data) {
        $sql = "INSERT INTO patient (name, birthday, gender) VALUES (:name, :birthday, :gender) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        $result = $stmt->fetch();
        return $result['id'];
    }


    public function getAll(
            $name,
            $conclusion,
            $sorting,
            $scheduledVisits,
            $onlyMine,
            $page,
            $offset,
            $size,
            $doctorId
    ) {
        {
            $sql = "SELECT * FROM patient WHERE 1=1";

            if ($name) {
                $sql .= " AND name LIKE :name";
            }

            if ($conclusion) {
                $sql .= " AND id IN (SELECT patient_id FROM inspection WHERE conclusion = :conclusion)";
            }

            if ($scheduledVisits) {
            $sql .= " AND id IN (SELECT patient_id FROM inspection WHERE date > NOW())";
            }

            if ($onlyMine) {
                $sql .= " AND id IN (SELECT patient_id FROM inspection WHERE doctor_id = :doctor_id)";
            }

            if ($sorting) {
                switch ($sorting) {
                    case 'NameAsc':
                        $sql .= " ORDER BY name ASC";
                        break;
                    case 'NameDesc':
                        $sql .= " ORDER BY name DESC";
                        break;
                    case 'CreateAsc':
                        $sql .= " ORDER BY created_at ASC";
                        break;
                    case 'CreateDesc':
                        $sql .= " ORDER BY created_at DESC";
                        break;
                    case 'InspectionAsc':
                        $sql .= " ORDER BY (SELECT MIN(date) FROM inspection WHERE patient_id = patient.id) ASC";
                        break;
                    case 'InspectionDesc':
                        $sql .= " ORDER BY (SELECT MIN(date) FROM inspection WHERE patient_id = patient.id) DESC";
                        break;
                }
            }

            $sql .= " LIMIT :size OFFSET :offset";

            $stmt = $this->pdo->prepare($sql);

            if ($name) {
                $stmt->bindValue(':name', '%' . $name . '%');
            }

            if ($conclusion) {
                $stmt->bindValue(':conclusion', $conclusion);
            }

            if ($onlyMine) {
                $stmt->bindValue(':doctor_id', $doctorId);
            }

            $stmt->bindValue(':size', $size);
            $stmt->bindValue(':offset', $offset);

            $stmt->execute();
            return $stmt->fetchAll();
            }
    }

    public function getPatientsCount(
            $name,
            $conclusion,
            $scheduledVisits,
            $onlyMine,
            $doctorId
        ) {
            $sql = "SELECT COUNT(*) AS total FROM patient WHERE 1=1";

            if ($name) {
                $sql .= " AND name LIKE :name";
            }

            if ($conclusion) {
                $sql .= " AND id IN (SELECT patient_id FROM inspection WHERE conclusion = :conclusion)";
            }

            if ($scheduledVisits) {
                $sql .= " AND id IN (SELECT patient_id FROM inspection WHERE date > NOW())";
            }

            if ($onlyMine) {
                $sql .= " AND id IN (SELECT patient_id FROM inspection WHERE doctor_id = :doctor_id)";
            }

            $stmt = $this->pdo->prepare($sql);

            if ($name) {
                $stmt->bindValue(':name', '%' . $name . '%');
            }

            if ($conclusion) {
                $stmt->bindValue(':conclusion', $conclusion);
            }

            if ($onlyMine) {
                $stmt->bindValue(':doctor_id', $doctorId);
            }

            $stmt->execute();
            return $stmt->fetch()['total'];
        }


    public function getById($id) {
        $sql = "SELECT * FROM patient WHERE id = :id"; 
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["id" => $id]);
        return $stmt->fetch();
    }


}