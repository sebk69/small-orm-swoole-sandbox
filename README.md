# small-orm-swoole-sandbox

## purpose

This is a Symfony testing app to test Small Orm Swoole Connector

## Install

```bash
# clone repo
git clone git@github.com:sebk69/small-orm-swoole-sandbox.git

# use docker-compose to build containers
cd small-orm-swoole-sandbox
docker-compose up -d --build
```

## Usage

The server is available on 'http//localhost:9501'

Here are routes :
```bash
./console debug:router
 ----------------------------------- -------- -------- ------ ----------------------------------- 
  Name                                Method   Scheme   Host   Path                               
 ----------------------------------- -------- -------- ------ ----------------------------------- 
  app_test_multipersist                    ANY      ANY      ANY    /multiPersist                      
  app_test_createproject                   ANY      ANY      ANY    /createProject/{name}              
  app_test_deleteprojects                  ANY      ANY      ANY    /deleteProjects                    
  app_test_unitmultipersist                ANY      ANY      ANY    /unitMultiPersist/{name}           
  app_test_persistwithpagination           ANY      ANY      ANY    /persistWithPagination             
  app_test_massfindone                     ANY      ANY      ANY    /massFindOne                       
  app_test_multipersistdoctrine            ANY      ANY      ANY    /doctrine/multiPersist             
  app_test_createprojectdoctrine           ANY      ANY      ANY    /doctrine/createProject/{name}     
  app_test_deleteprojectsdoctrine          ANY      ANY      ANY    /doctrine/deleteProjects           
  app_test_unitmultipersistdoctrine        ANY      ANY      ANY    /doctrine/unitMultiPersist/{name}  
  app_test_persistwithpaginationdoctrine   ANY      ANY      ANY    /doctrine/persistWithPagination    
  app_test_massfindonedoctrine             ANY      ANY      ANY    /doctrine/massFindOne              
  _preview_error                           ANY      ANY      ANY    /_error/{code}.{_format}           
 ----------------------------------- -------- -------- ------ -----------------------------------
 ```

## Benchmark

This is an average time for 100 tests in same conditions and swoole instance.

```
/multiPersist                      129ms (transactional persist of 1000 records)
/createProject/{name}              16ms (create 100 models and persist)
/deleteProjects                    1320ms (delete 1000 records)
/unitMultiPersist/{name}           1418ms (unitary persist and flush 1000 records)
/persistWithPagination             1418ms (paginate 1000 records by 10 and unitary persist and flush every records)
/massFindOne                       169ms  (1000 findOne)
```
