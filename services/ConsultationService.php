<?php
class ConsultationService {
    private $consultationModel;
    private $doctorModel;

    public function __construct($consultationModel, $doctorModel) {
        $this->consultationModel = $consultationModel;
        $this->doctorModel = $doctorModel;
    }
    

    public function createConsultations($data, $inspectionId, $doctorId) {
        foreach($data as $consultation) {
            if(!UUIDValidator::isValid($consultation['speciality_id'])) {
                throw new Exception('invalid id format', 400) ;  
        }
        }
        foreach ($data as $consultation) {
            $consultationId = $this->consultationModel->create($consultation['speciality_id'], $inspectionId);
            $this->consultationModel->createBaseComment($consultation['comment'], $consultationId, $doctorId);
        }
    }

    public function createCommentForConsultation($data, $consultationId, $doctorId) {
        if(!UUIDValidator::isValid($consultationId)) {
         throw new Exception('invalid id format', 400) ;  
        }

        if(!isset($data['content'])) {
           throw new Exception("Invalid arguments", 400); 
        }

        $consultation = $this->consultationModel->getConsultationById($consultationId);
        if(!$consultation) {
            throw new Exception("consultation not found", 404);
        }
        
        $doctor = $this->doctorModel->getById($doctorId);
        if($doctor['speciality_id'] !== $consultation['speciality_id'] and $doctor['id'] !== $consultation['doctor_id']) {
            throw new Exception("User doesn't have add comment to consultation (unsuitable specialty and not the inspection author)", 403);
        }

        if(!isset($data['parentId'])) {
            return $this->consultationModel->createBaseComment($data, $consultationId, $doctorId);
        } else {
           $parrentComment = $this->consultationModel->getCommentById($data['parentId']);

           if(!$parrentComment) throw new Exception("parent comment not found", 404);

           else return $this->consultationModel->createNestedComment($data, $consultationId, $doctorId);
        }
    }

    public function getConsultationById($id) {
        if(!UUIDValidator::isValid($id)) {
         throw new Exception('invalid id format', 400) ;  
        }

        $result = $this->consultationModel->getConsultationById($id);

        if(!$result) {
           throw new Exception("consultation not found", 404);
        }

        $consultation = [
            'id' => $result['consultation_id'],
            'createTime' => $result['consultation_createtime'],
            'inspectionId' => $result['inspection_id'],
            'speciality' => [
                'id' => $result['speciality_id'],
                'createTime' => $result['speciality_createtime'],
                'name' => $result['speciality_name']
            ],
            'comments' => $this->getAllCommentsOfConsultation($id)
        ];
        return $consultation;
    }

    public function getAllCommentsOfConsultation($id) {
        if(!UUIDValidator::isValid($id)) {
         throw new Exception('invalid id format', 400) ;  
        }

        $results = $this->consultationModel->getAllCommentsOfConsultation($id);

        $comments = [];
        foreach ($results as $result) {
            $comment = [
                'id' => $result['comment_id'],
                'createTime' => $result['comment_createtime'],
                'modifiedDate' => $result['comment_modified_date'],
                'content' => $result['content'],
                'authorId' => $result['author_id'],
                'author' => $result['author_name'],
                'parentId' => $result['parent_id']
            ];
            $comments[] = $comment;
        }
        return $comments;
    }


    public function updateComment($data, $id, $doctorId) {
        if(!UUIDValidator::isValid($id)) {
         throw new Exception('invalid id format', 400) ;  
        }

        $comment = $this->consultationModel->getCommentById($id);
        if(!$comment) {
            throw new Exception("comment not found", 404);
        }

        if(!isset($data['content'])) {
            throw new Exception("Invalid arguments", 400);
        }

        $doctor = $this->doctorModel->getById($doctorId);

        if($comment['doctor_id'] != $doctor['id']) {
            throw new Exception("User is not the author of the comment", 403);
        }

        $result = $this->consultationModel->updateComment($data, $id);
        return $result;
    }

    public function getInspectionsWithConsultations($doctorId, $grouped, $page, $size){
        $doctor = $this->doctorModel->getById($doctorId);
        $offset = ($page - 1) * $size;
        $results = $this->consultationModel->getInspectionsWithConsultations($doctor['speciality_id'], $grouped, $offset, $size);

        $inspections = [];

        if(!$results) return $inspections;
        
        foreach ($results as $result) {
            $inspection = [
                'id' => $result['id'],
                'createTime' => $result['createtime'],
                'previousId' => $result['previousinspectionid'],
                'date' => $result['date'],
                'conclusion' => $result['conclusion'],
                'doctorId' => $result['doctor_id'],
                'doctor' => $result['doctor_name'],
                'patientId' => $result['patient_id'],
                'patient' => $result['patient_name'],
                'diagnosis' => [
                    'id' => $result['diagnosis_id'],
                    'createTime' => $result['diagnosis_createtime'],
                    'code' => $result['diagnosis_code'],
                    'name' => $result['diagnosis_name'],
                    'description' => $result['diagnosis_description'],
                    'type' => $result['diagnosis_type'],
                ],
                'hasChain' => $result['has_chain'],
                'hasNested' => $result['has_nested'],
             ];

             $inspections[] = $inspection;
        }

        $totalCount = $this->consultationModel->getInspectionsCount($doctor['speciality_id']);

        $pagination = [
            'size' => $size,
            'count' => ceil($totalCount / $size),
            'current' => $page
        ];

        return ['inspections' => $inspections, 'pagination' => $pagination];
    }
}