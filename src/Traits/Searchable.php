<?php


namespace BasharQarat\Simsearch\Traits;


use BasharQarat\Simsearch\Classes\SearchByModelFieldsClass;
use BasharQarat\Simsearch\Classes\SearchByModelRelationsClass;
use Illuminate\Database\Eloquent\Builder;

trait Searchable
{

    private $separatorBetweenRelationFields = ',';
    /**
     * notice: the search scope should be the first function called before other where function
     * @uses search('lara',[],['name','job'])
     * @uses search('lara',[],['name','job','Work.name']) //Work is relation name
     * @uses search('lara',[],['name','job','Work.name','Location.[country,state,city]]) // if you want to search in relation in many fields
     * @uses search('lara') or search('lara') will search in model searchable field $mySearchableFields
     * @uses search('lara',[]) or search('lara') will search in model searchable field $mySearchableFields
     * @uses search('lara',['Work'],[])  will search in model searchable fields $mySearchableFields and in searchable fields in the relation Work
     * @uses search('lara',['Work.Location'],[])  will search in model searchable fields $mySearchableFields and in searchable fields in the relation Work and searchable fields in Location
     * but what if i want to search in to relation related with Work like Location and User so
     * @uses search('lara',['Work'=>['Location','User']],[])  will search in model searchable fields $mySearchableFields and in searchable fields in the relation Work and searchable fields in Location and in User searchable fields
     * @param string $key (querySearchKey)
     * @param array $fields (array of strings) if its empty then we will search
     * in getSearchableFields array
     * @param array $relations (array of relation names you want to search on all them fields too by relation)
     * @param array $expressionFields its explained bellow in method isExpressionField()
     * @return Builder
     * @throws \Exception
     * if you send just the key will call getSearchableFields() method
     * that return [] (if you didn't override it before)
     * you can override it to put your model searchable fields
     * to make all default search on it
     */
    public function scopeSearch($query,$searchKey,array $relations=[],array $fields=[],array $expressionFields=[]){

        if(empty($searchKey)){
            return $query;
        }

        if(empty($fields)){
            $fields = $query->getModel()->getSearchableFields();
        }

        $this->generateQuery($query,$fields,$relations,$searchKey,$expressionFields);
        return $query;
    }


    public function generateQuery($query,$fields,$relations,$searchKey,$expressionFields){
        $query->where(function($query)use($fields,$relations,$searchKey,$expressionFields){

            if(empty($fields)) {//if we have used relation and its $mySearchableFields empty that mean the query will execute ( or exists .....) without any conditions ,and it's always true like when we trying to reach from pivot table to another
                $query->where('id', -1);
            }

            $searchByModelFieldsClass = new SearchByModelFieldsClass($query,$fields,$searchKey,$expressionFields,$this->separatorBetweenRelationFields);
            $query = $searchByModelFieldsClass->search();

            $searchByModelRelationsClass = new SearchByModelRelationsClass($query,$relations,$searchKey,$expressionFields,$this->separatorBetweenRelationFields);
            $query = $searchByModelRelationsClass->search();


        });
        return $query;
    }




    /**
     * you should override this method and make it return array contain
     * your model fields to search in it
     */
    public function getSearchableFields(){
        return $this->mySearchableFields ?? [];
    }


}
