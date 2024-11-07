<?php

class DictionaryService {
    private $model;

    public function __construct($model) {
        $this->model = $model;
    }


    public function getSpecialtiesList($name, $page, $size) {
        if (!is_string($name)) {
            throw new InvalidArgumentException('name must be a string');
        }

        if (!is_numeric($page) || $page <= 0) {
            throw new InvalidArgumentException('page must be a positive number');
        }

        if (!is_numeric($size) || $size <= 0) {
            throw new InvalidArgumentException('size must be a positive number');
        }

        $offset = ($page - 1) * $size;

        $specialties = $this->model->getSpecialtiesList($name, $offset, $size);
        $totalCount = $this->model->getSpecialtiesCount($name);
        $response = [
            'specialties' => $specialties,
            'pagination' => [
                'size' => $size,
                'count' => ceil($totalCount / $size),
                'current' => $page
            ]
        ];

        return $response;
    }

    public function getIcd10List($request, $page, $size) {
        if (!is_string($request)) {
            throw new InvalidArgumentException('name must be a string');
        }

        if (!is_numeric($page) || $page <= 0) {
            throw new InvalidArgumentException('page must be a positive number');
        }

        if (!is_numeric($size) || $size <= 0) {
            throw new InvalidArgumentException('size must be a positive number');
        }

        $offset = ($page - 1) * $size;

        $records = $this->model->getIcd10List($request, $offset, $size);
        $totalCount = $this->model->getIcd10Count($request);
        $response = [
            'records' => $records,
            'pagination' => [
                'size' => $size,
                'count' => ceil($totalCount / $size),
                'current' => $page
            ]
        ];

        return $response;
    }

    public function getIcd10roots() {
        return $this->model->getIcd10Roots();
    }

}