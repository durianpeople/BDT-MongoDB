# BDT MongoDB

## Implementasi Cluster MongoDB

### Konfigurasi Vagrant

Dibuat sebuah cluster yang terdiri dari beberapa Vagrant VM yang meliputi: dua *config server*, tiga *shard server*, dan satu *query router* dengan pembagian alamat IP sebagai berikut:

```
192.168.33.11   mongo-config-1
192.168.33.12   mongo-config-2
192.168.33.13   mongo-shard-1
192.168.33.14   mongo-shard-2
192.168.33.15   mongo-shard-3
192.168.33.16   mongo-query-router
```

Untuk setiap VM, konfigurasi di atas ditambahkan ke dalam `/etc/hosts`

### Pengaturan Autentikasi MongoDB

Pada `mongo_config_1` , masuk ke dalam shell mongo:

```
mongo
```

Masukkan query berikut untuk menambahkan user:

```
use admin
db.createUser({user: "mongo-admin", pwd: "password", roles:[{roles: "root", db: "admin"}]})
```



### Konfigurasi Config Server

Tambahkan konfigurasi berikut ke dalam `/etc/mongod.conf`:

- Untuk memasang mongod service ke IP dan port tertentu:

  ```
  port: 27019
    bindIp: [IP config server]
  ```

- Memberi nama pada replication set

  ```
  replication:
    replSetName: configReplSet
  ```

- Mengubah role cluster sebagai config server:

  ```
  sharding:
    clusterRole: "configsvr"
  ```

Restart service mongod

```
sudo systemctl restart mongod
```

Lakukan perintah berikut untuk masuk ke shell mongo pada **salah satu** VM:

```
mongo mongo-config-1:27019 -u mongo-admin -p --authenticationDatabase admin
```

---

Sempat terjadi error pada saat mencoba terhubung ke database, dalam kasus saya, saat mencoba bind port ke 27019. Perintah berikut dapat menyelesaikan kasus ini:

```
sudo rm /tmp/mongodb-27019.sock
```

---

Di dalam shell mongo, masukkan perintah berikut untuk inisialisasi replica set:

```
rs.initiate( { _id: "configReplSet", configsvr: true, members: [ { _id: 0, host: "mongo-config-1:27019" }, { _id: 1, host: "mongo-config-2:27019" } ] } )
```

### Konfigurasi Shard Server

Untuk setiap shard server, tambahkan ke `/etc/mongod.conf`:

```
net:
  port: 27017
  bindIp: [IP SHARD]
sharding:
  clusterRole: "shardsvr"
```

### Konfigurasi Query Router

Buat file `/etc/mongos.conf` :

```
# where to write logging data.
systemLog:
  destination: file
  logAppend: true
  path: /var/log/mongodb/mongos.log

# network interfaces
net:
  port: 27017
  bindIp: 192.168.33.16

security:
  keyFile: /opt/mongo/mongodb-keyfile

sharding:
  configDB: configReplSet/mongo-config-1:27019,mongo-config-2:27019,mongo-config-3:27019
```

Buat service baru dengan membuat file `/lib/systemd/system/mongos.service`:

```
[Unit]
Description=Mongo Cluster Router
After=network.target

[Service]
User=mongodb
Group=mongodb
ExecStart=/usr/bin/mongos --config /etc/mongos.conf
# file size
LimitFSIZE=infinity
# cpu time
LimitCPU=infinity
# virtual memory size
LimitAS=infinity
# open files
LimitNOFILE=64000
# processes/threads
LimitNPROC=64000
# total threads (user+kernel)
TasksMax=infinity
TasksAccounting=false

[Install]
WantedBy=multi-user.target
```

Matikan `mongod` dan nyalakan `mongos.service`:

```
sudo systemctl stop mongod
sudo systemctl enable mongos.service
sudo systemctl start mongos
```

### Konfigurasi Shard Server

Masuk ke **query server**  melalui **salah satu** shard server:

```
mongo mongo-query-router:27017 -u mongo-admin -p --authenticationDatabase admin
```

Tambahkan shard member:

```
sh.addShard( "mongo-shard-1:27017" )
sh.addShard( "mongo-shard-2:27017" )
sh.addShard( "mongo-shard-3:27017" )
```

Buat database dan nyalakan sharding-nya

```
use fighterDetails
sh.enableSharding("fighterDetails")
```

Buat collection baru dan gunakan strategi sharding *hash*:

```
db.fighterCollection.ensureIndex( { _id : "hashed" } )
sh.shardCollection( "fighterDetails.fighterCollection", { "_id" : "hashed" } )
```

## Menentukan Dataset

Dataset yang digunakan adalah *raw_fighter_details* dari UFC - Fight Historical Data dari Kaggle, dengan kolom-kolom:

- fighter_name
- Height
- Weight
- Reach
- Stance
- DOB

## Implementasi Aplikasi CRUD

Aplikasi CRUD berupa *command-line interface* menggunakan bahasa pemrograman PHP dan framework PuzzleOS (https://github.com/maralproject/puzzleos). 

Extension MongoDB untuk PHP dapat dipasang menggunakan `pecl`. Library MongoDB untuk PHP dapat di-*install* menggunakan `composer require mongodb/mongodb`, dalam project ini dilakukan di dalam folder `lib/`

Untuk mendapatkan collection:

```php
$client = new Client("mongodb://mongo-admin:password@192.168.33.16:27017/fighterDetails?retryWrites=false&authSource=admin");
$db = $client->fighterDetails;
/** @var Collection $collection */
$collection = $db->fighterCollection;
```

- Untuk mendapatkan semua document dalam collection

  ```php
  $cursor = self::$collection->find([], [
      'sort' => [
          '_id' => -1,
      ],
  ]);
  $results = [];
  foreach ($cursor as $document) {
      $results[] = $document->bsonSerialize();
  }
  ```

- Untuk mendapatkan semua document yang memiliki value tertentu pada kolom tertentu (dalam kasus ini kolom *fighter_name*):

  ```php
  $results = self::$collection->find([
      "fighter_name" => $name,
  ]);
  ```

- Untuk memasukkan document ke dalam collection (definisikan semua nama kolom dan value-nya)

  ```php
  self::$collection->insertOne([
      'fighter_name' => $fighter_name,
      'Height' => Converter::cmToFeet($Height),
      'Weight' => Converter::kgToLb($Weight),
      'Reach' => Converter::cmToFeet($Reach, true),
      'Stance' => $Stance,
      'DOB' => Converter::europeDateToAmerican($DOB)
  ]);
  ```

- Untuk melakukan update terhadap satu document dengan nilai kolom tertentu:

  ```php
  $updates = [];
  if ($args['--fighter_name']) $updates['fighter_name'] = $args['--fighter_name'];
  if ($args['--height']) $updates['Height'] = $args['--height'];
  if ($args['--weight']) $updates['Weight'] = $args['--weight'];
  if ($args['--reach']) $updates['Reach'] = $args['--reach'];
  if ($args['--stance']) $updates['Stance'] = $args['--stance'];
  if ($args['--dob']) $updates['DOB'] = $args['--dob'];
  $collection->updateOne([
      '_id' => new \MongoDB\BSON\ObjectID($args['--hash']),
  ], [
      '$set' => $updates, // $updates merupakan array
  ]);
  ```

- Untuk menghapus satu document

  ```php
  $collection->deleteOne([
      '_id' => new \MongoDB\BSON\ObjectID($args['--hash']),
  ]);
  ```

- Untuk melakukan agregasi (count):

  ```php
  $results = $collection->aggregate([
      [
          '$group' => [
              "_id" => '$Stance',
              "count" => [
                  '$sum' => 1,
              ]
          ]
      ],
  ]);
  ```

- Untuk melakukan agregasi (max):

  ```php
  $results = $collection->aggregate([
      [
          '$group' => [
              "_id" => '$Stance',
              "max" => [
                  '$max' => '$Weight',
              ]
          ]
      ],
  ]);
  ```

  