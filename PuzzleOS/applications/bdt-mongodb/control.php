<?php

use BDT\Controller\Fighter;
use MongoDB\Client;
use MongoDB\Collection;

if (is_cli()) {
    set_time_limit(0);
    $client = new Client("mongodb://mongo-admin:password@192.168.33.16:27017/fighterDetails?retryWrites=false&authSource=admin");
    $db = $client->fighterDetails;
    /** @var Collection $collection */
    $collection = $db->fighterCollection;
    PuzzleCLI::register(function ($io, $args) use ($collection) {
        $executed = 0;
        Fighter::init($collection, $io);
        if ($args["list"]) {
            $executed++;
            if ($args['--limit']) $limit = $args['--limit'];
            else $limit = 0;
            Fighter::list($limit);
        } else if ($args['find']) {
            if ($args['--name']) {
                $executed++;
                Fighter::findByName($args['--name']);
            } else if ($args['--hash']) {
                $executed++;
                Fighter::findByHash($args['--hash']);
            }
        } else if ($args['insert']) {
            if ($args['--fighter_name'] && ($args['--height'] || $args['--weight'] || $args['--reach'] || $args['--stance'] || $args['--dob'])) {
                $executed++;
                Fighter::insert(
                    $args['--fighter_name'],
                    $args['--height'],
                    $args['--weight'],
                    $args['--reach'],
                    $args['--stance'],
                    $args['--dob']
                );
            }
        } else if ($args['update']) {
            if ($args['--hash']) {
                $executed++;
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
                    '$set' => $updates,
                ]);
            }
        } else if ($args['delete']) {
            if ($args['--hash']) {
                $executed++;
                $collection->deleteOne([
                    '_id' => new \MongoDB\BSON\ObjectID($args['--hash']),
                ]);
            }
        } else if ($args['stance-distrib']) {
            $executed++;
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
            $io->out(str_pad("Stance", 15, " ", STR_PAD_RIGHT));
            $io->out("\t\t");
            $io->out(str_pad("Count", 15, " ", STR_PAD_RIGHT));
            $io->out("\n");
            foreach ($results as $result) {
                $io->out(str_pad($result['_id'], 15, " ", STR_PAD_RIGHT));
                $io->out("\t\t");
                $io->out(str_pad($result['count'], 15, " ", STR_PAD_RIGHT));
                $io->out("\n");
            }
        } else if ($args['stance-maxweight']) {
            $executed++;
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
            $io->out(str_pad("Stance", 15, " ", STR_PAD_RIGHT));
            $io->out("\t\t");
            $io->out(str_pad("Max Weight", 15, " ", STR_PAD_RIGHT));
            $io->out("\n");
            foreach ($results as $result) {
                $io->out(str_pad($result['_id'], 15, " ", STR_PAD_RIGHT));
                $io->out("\t\t");
                $io->out(str_pad($result['max'], 15, " ", STR_PAD_RIGHT));
                $io->out("\n");
            }
        }

        if ($executed == 0) {
            $io->out("Perintah yang tersedia: \n");
            $io->out("\t list --limit [limit]: Menampilkan daftar fighter\n");
            $io->out("\t find --name (string): Mencari data\n");
            $io->out("\t insert (--fighter_name [string] | --height [cm]] | --weight [kg] | --reach [cm] | --stance [string] | --dob [dashed-date]: Memasukkan data\n");
            $io->out("\t update --hash [_id] (--fighter_name [string] | --height [cm]] | --weight [kg] | --reach [cm] | --stance [string] | --dob [dashed-date]: Mengubah data\n");
            $io->out("\t delete --hash [_id]: Menghapus data\n");
            $io->out("\t stance-distrib: Distribusi stance\n");

            $io->out("\n");
        } else $io->out("Perintah berhasil dilakukan.\n");
    });
}
