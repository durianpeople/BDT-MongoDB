# BDT MongoDB

## Implementasi Cluster MongoDB

### Rencana Konfigurasi

Dibuat sebuah cluster yang terdiri dari beberapa Vagrant VM yang meliputi: dua *config server*, tiga *shard server*, dan satu *query router* dengan pembagian alamat IP sebagai berikut:

```
192.168.33.11   mongo-config-1
192.168.33.12   mongo-config-2
192.168.33.13   mongo-shard-1
192.168.33.14   mongo-shard-2
192.168.33.15   mongo-shard-3
192.168.33.16   mongo-query-router
```
