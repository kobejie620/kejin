<?php

namespace Apps\Model;

class AppsSurveyModel extends AppsModel {

	public function getAll($where = array("status" => 2)) {
		return $this->where($where)->select();
	}

}
