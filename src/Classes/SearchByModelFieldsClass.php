<?php

namespace BasharQarat\Simsearch\Classes;


class SearchByModelFieldsClass
{

    private $query;
    private $fields;
    private $searchKey;
    private $expressionFields;
    private $separatorBetweenRelationFields;

    public function __construct($query,$fields, $searchKey, $expressionFields,$separatorBetweenRelationFields){
        $this->query = $query;
        $this->fields = $fields;
        $this->searchKey = $searchKey;
        $this->expressionFields = $expressionFields;
        $this->separatorBetweenRelationFields = $separatorBetweenRelationFields;
    }

    public function search(){
        //search in model searchable fields
        foreach ($this->fields as $index => $field) {
            if ($this->isRelation($field)) {
                list($relation,$relationFields) = $this->analysesRelationFields($field);

                $this->query->orWhereHas($relation, function ($q) use ($relationFields){
                    $q->search($this->searchKey, [],$relationFields, $this->expressionFields);
                });

            } else {
                $field = $this->isExpressionField($field,$this->expressionFields);
                $this->query->orWhere($field, 'like', '%' . $this->searchKey . '%');

            }

        }
        return $this->query;

    }

    /**
     * @param string $field
     * @return bool
     * check if the field string contains dot that mean this field is relationship
     */
    private function isRelation($field):bool
    {
        return str_contains($field,'.');
    }


    private function analysesRelationFields($field){
        list($relation,$relationFields) = explode('.',$field);

        $this->isValidRelationFormat($relation,$relationFields);
        $relationFields = $this->getRelationFields($relationFields);
        return [$relation,$relationFields];
    }

    private function isValidRelationFormat($relationName,$relationFields){
        if(empty($relationName))
            throw new \Exception('you have been missed the relation name - search action');

        if(empty($relationFields))
            throw new \Exception('you have been missed the parameter in relation '.$relationName.' - search action');

    }

    /**
     * @param string $relationFields
     * @return array
//     * @throws ErrorMsgException
     */
    private function getRelationFields($relationFields):array
    {
        if($this->isArrayRelationFields($relationFields))
            $fieldsArray = $this->getFieldsFromArray($relationFields);
        else
            $fieldsArray = [$relationFields];
        return $fieldsArray;
    }

    /**
     * @param string $relationFields
     * @return bool
//     * @throws ErrorMsgException
     * check if the given string its array return true
     * else it is single filed ,so we check if there is missing [ from the start
     * or ] in the last
     */
    private function isArrayRelationFields($relationFields):bool
    {
        $firstChar = $relationFields[0];
        $lastCharIndex = strlen($relationFields) - 1;
        $lastChar = $relationFields[$lastCharIndex];
        if ($firstChar == '[' && $lastChar == ']')
            return true;

        if(($firstChar == '[' && $lastChar != ']')||
            ($firstChar != '[' && $lastChar == ']'))
            throw new \Exception('error in array syntax of relation fields : '.$relationFields);

        return false;

    }

    /**
     * @param string $relationFields
     * @return array
     * explodes the string to fields
     */
    private function getFieldsFromArray($relationFields):array
    {
        $lastCharIndex = strlen($relationFields) - 1;
        // $lastCharIndex-1 because we dont want get until the char before lastChar
        $fields = substr($relationFields,1,$lastCharIndex-1);
        $fieldsArray = explode($this->separatorBetweenRelationFields,$fields);
        return $fieldsArray;
    }


    /**
     * check if the field exists as key in $expressionFields array
     * if exists then return the new value of it
     * else return his original value
     * we use this function for fields need query like
     * \DB::raw('CONCAT(fname," ",lname)')
     * example:
     * Model::search('lara',
     *              ['name','User.[concatName,country]'],
     *              [],
     *              ['concatName' => \DB::raw('CONCAT(fname," ",lname)')
     *            ])
     * so the original value of concatName its concatName
     * and the real value we should get it from the 3 parameter in search function
     * so that mean the real value of concatName its $array['concatName']
     * and its equal \DB::raw('CONCAT(fname," ",lname)')
     * so if the field is key in that array => the filed has real and original value
     */
    public function isExpressionField($field,array $expressionFields){
        //maybe the field will be Expression object
        if(!is_integer($field) && !is_string($field))
            return $field;
        if(count($expressionFields)>0 && array_key_exists($field,$expressionFields))
            return $expressionFields[$field];
        return $field;
    }

}