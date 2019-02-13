<?php

namespace Apps\Model;

class AppsSurveyProblemModel extends AppsModel {

	public function getAll($where = array("status" => 2)) {
		return $this->where($where)->select();
	}

}
