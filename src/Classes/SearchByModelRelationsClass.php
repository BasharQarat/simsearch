<?php

namespace BasharQarat\Simsearch\Classes;

use App\Exceptions\ErrorMsgException;

class SearchByModelRelationsClass
{

    private $query;
    private $relations;
    private $searchKey;
    private $expressionFields;
    private $separatorBetweenRelationFields;

    public function __construct($query,$relations, $searchKey,$expressionFields,$separatorBetweenRelationFields){
        $this->query = $query;
        $this->relations = $relations;
        $this->searchKey = $searchKey;
        $this->expressionFields = $expressionFields;
        $this->separatorBetweenRelationFields = $separatorBetweenRelationFields;

    }


    public function search(){
        //search in relation fields
        foreach ($this->relations as $relationKey=>$value) {
            /**
             * @var array $nestedRelationsInManyWays
             * @var string $relationName
             */
            list($relationName, $nestedRelationsInManyWays) =
                $this->analysesNestedInManyWays($relationKey,$value);//User=>[Target,Teacher..]

            //the last item of nested relation will be empty
            if (empty($relationName))
                return $this->query;

            list($relationName, $nestedRelations) =
                $this->analysesNestedRelation($relationName);//User.Target...

            $fields = [];
            list($relationName,$fields) = $this->analysesRelationFields($relationName);


            /**
             * @var array $finalNestedRelations
             */
            $finalNestedRelations = [$nestedRelations];
            if(!empty($nestedRelationsInManyWays)){
                //didn't reach to the last nestedRelation yet
                if(!empty($nestedRelations))
                    $finalNestedRelations = [$nestedRelations=>$nestedRelationsInManyWays];
                else
                    $finalNestedRelations = $nestedRelationsInManyWays;
            }

//            echo $query->getModel()->getTable(). ' \n';
            if (!method_exists($this->query->getModel(), $relationName))
                throw new \Exception('invalid relation name');
//                throw new ErrorMsgException('invalid relation name');
            $this->query->orWhereHas($relationName, function ($q) use ( $fields,$finalNestedRelations) {
                return $q->search($this->searchKey,$finalNestedRelations,$fields,$this->expressionFields);
            });
        }
        return $this->query;

    }


    /**
     * when trying to search on 2 or more relation for defined relation
     * ex:
     *  ['User'=>['Student','Address']]
     */
    private function analysesNestedInManyWays($key,$value){
        $relationName = $value;
        $moreThanOneNestedRelations = [];
        if(!is_int($key)){
            $relationName = $key;
            $moreThanOneNestedRelations = $value;
        }
        return [$relationName,$moreThanOneNestedRelations];
    }

    private function analysesNestedRelation($relationName){
        $otherLevelOfMultipleRelation = '';
        if ($this->isMultipleRelation($relationName)) {
            list($relationName, $otherLevelOfMultipleRelation) =
                $this->separateFirstLevelOfMultipleRelation($relationName);
        }
        return [$relationName,$otherLevelOfMultipleRelation];
    }


    private function isMultipleRelation($field):bool
    {
        return str_contains($field,'.');
    }

    /**
     * separate the multiple relation to relation and other multiple
     * ex: the field is : Student.User.Address
     * then the result is ->
     * array[0] = 'Student'
     * array[1] = 'User.Address'
     */
    private function separateFirstLevelOfMultipleRelation($multipleRelationName){
        return explode('.',$multipleRelationName,2);
    }



    private function analysesRelationFields($relation){
        if(!$this->isRelationWithFields($relation))
            return [$relation,[]];

        list($relationName,$relationFields) = explode(':',$relation);

        $this->isValidRelationFieldFormat($relationName,$relationFields);
        $relationFields = $this->getRelationFields($relationFields);
        return [$relationName,$relationFields];
    }

    private function isRelationWithFields($relationWithFields){
        if(str_contains($relationWithFields,':'))
            return true;
        return false;
    }

    private function isValidRelationFieldFormat($relationName,$relationFields){
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
        return $this->getFieldsFromArray($relationFields);
    }

    /**
     * @param string $relationFields
     * @return array
     * explodes the string to fields
     */
    private function getFieldsFromArray($relationFields):array
    {
        return explode($this->separatorBetweenRelationFields,$relationFields);

    }



}