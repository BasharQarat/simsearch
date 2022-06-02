# Simsearch

dynamic simple search 


## Installation
composer require basharqarat/simsearch

## Usage

lets talk about the function signature and response

    Model::search($var1,$var2,$var3,$var4);

$var1(required) : is the string we searching about it .

$var2(optional) : is array of relations models we want to search in it too ,and if it empty => the function will ignore it.

$var3(optional) : is array of strings (if we want to search in defined columns) ,adn if it empty => the function will ignore it.

$var4(optional) : is array and keys of expressions

response : collection of model data ,or empty array.

# usage in model
after the Installation.

in your models you want to search in it:

    use BasharQarat\Simsearch\Traits\Searchable;

    class User extends Authenticatable
    {
        use Searchable;
    

        public $mySearchableFields = [
            'name',
            'fname',
            'lname',
            'phone_code',
            'phone_number'
            'email',
        ];
        
        /**
        * return array
        */
        public function getSearchableFields(){
            return array_merge($this->mySearchableFields,
                [
                    \DB::raw('CONCAT(phone_code," ",phone_number)'),
                    \DB::raw('CONCAT(fname," ",lname)')
                ];
           );
        }

    }

1- use the Searchable Traits.

2- add $mySearchableFields array and put inside it your columns name you want to search dynamically on It.

3- if you want to use expressions in with your fields like(concat(fname,'',lname)) so you should use the getSearchableFields() function in your model like the previuos

4- now you can use it in your code.


# logic
# search in same model
after the initiallizing a model.

1- this will search in the user model in all columns inside $mySearchableFields array

    User::search($search_key)
    ->get();

 but what if i want to search in defined column every time ? you can do this the below .

2- this will search in the user model in name column and ignored the $mySearchableFields

    User::search($search_key,[],['name',...])
    ->get();
    
3- if you want to use expressios in defined query you should use unique doesnt declared in your model name in fields array like(full_name)
and pass the expressions array parameter and declare the same name of the new declared name as key and the value its the expression

    User::search($search_key,[],['full_name'],['full_name'=>\DB::raw('CONCAT(fname," ",lname)')])
    ->get();

# search in relations too
first you should have the relations functions in your models and use the searchable trait in all models you want to search in it.

    class User extends Authenticatable
    {
        use Searchable;
    

        public $mySearchableFields = [
            'name',
            'email',
        ];

        public function UserTypes(){
            return $this->hasMany('App\Models\UserType');
        }

    }

    class UserType extends Model
    {
        use Searchable;

        public $mySearchableFields = [
            'type',
        ];

        public function User(){
            return $this->belongsTo('App\Models\User');
        }

    }

and then you can use it like :

1- search in the User model and in his related UserTypes records

    User::search($search_key,['UserTypes'])
    ->get();

2- search in defined columns in the relations

    User::search($search_key,['UserTypes:type'])
    ->get();
and also we still can search in defined column from the user model too

    User::search($search_key,['UserTypes:type'],['name'])
    ->get();

or we can write it in this way:

    User::search($search_key,[],['name','UserTypes.type'])
    ->get();

what if we want more than one defined column?
so ...

    User::search($search_key,['UserTypes:type,id'])
    ->get();

 or

    User::search($search_key,[],['name','UserTypes.[type,id]'])
    ->get();
(when we want to put relation in same model fileds array must being in that way [for defined columns] )

# in nested relations
what if you want to search in nested relations and pass from one model to another?
so..

1- this will search in User model and UserTypes model and RelationNameRelatedToUserTypeModel model
depends on the name column in user model and $mySearchableFields array in other models of them.
(but in this way should the User model have UserTypes() function and UserType model should have inside it RelationNameRelatedToUserTypeModel() function )

    User::search($search_key,['UserTypes.RelationNameRelatedToUserTypeModel'],['name'])
    ->get();

2- as same for this one:

    User::search($search_key,['UserTypes.RelationNameRelatedToUserTypeModel.AnotherOneRelated_to_the_previous_RelationNameRelatedToUserTypeModel'],['name'])
    ->get();

3- if you want to search in 2 relations or more are related to the same model

    User::search($search_key,['UserTypes'=>['firstRelationRelatedToUserTypesModel','secondRelationRelatedToUserTypesModel',..]])
    ->get();

and we still use defined columns with all the AnotherOneRelated_to_the_previous_RelationNameRelatedToUserTypeModel:

    User::search($search_key,['UserTypes:type'=>['firstRelationRelatedToUserTypesModel:firstColumnName,secondColumnName','secondRelationRelatedToUserTypesModel',..]])
    ->get();

4- we can use the 2 types of nested relations together

    User::search($search_key,['UserTypes.RelationInsideUserTypes'=>['firstRelationRelatedToRelationInsideUserTypesModel','secondRelationRelatedToRelationInsideUserTypesModel',..]])
    ->get();


#note
we can use the expressions with the relation too in all the previuos states
    
    User::search($search_key,['UserTypes:expression_name'],[],['expression_name'=>\DB::raw('any expression here...')])
    ->get();



## Support

contact at bkbesho@gmail.com

