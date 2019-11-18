<?php

namespace BDT\Controller;


use MongoDB\Client;
use MongoDB\Collection;
use BDT\Controller\Converter;

class Fighter
{
    /** @var Collection $collection */
    public static $collection = null;
    public static $io = null;
    public static function init(Collection $collection, $io): void
    {
        self::$collection = $collection;
        self::$io = $io;
    }

    private static function printHeader(): void
    {
        self::$io->out(str_pad("Hash",  26, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad("Fighter Name", 30, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad("Height", 15, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad("Weight", 15, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad("Reach", 8, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad("Stance", 15, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad("DOB", 15, " ", STR_PAD_RIGHT));
        self::$io->out("\n");
        self::$io->out(str_pad("", 97, "-"));
        self::$io->out("\n");
    }

    private static function printRow(
        $hash,
        $fighter_name,
        $Height,
        $Weight,
        $Reach,
        $Stance,
        $DOB
    ): void {
        self::$io->out(str_pad($hash, 26, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad($fighter_name, 30, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad($Height, 15, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad($Weight, 15, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad($Reach, 8, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad($Stance, 15, " ", STR_PAD_RIGHT));
        self::$io->out("\t\t");
        self::$io->out(str_pad($DOB, 15, " ", STR_PAD_RIGHT));
        self::$io->out("\n");
    }

    public static function list($limit = 0): void
    {
        self::printHeader();
        $cursor = self::$collection->find([], [
            'sort' => [
                '_id' => -1,
            ],
        ]);
        $results = [];
        foreach ($cursor as $document) {
            $results[] = $document->bsonSerialize();
        }
        $c = 0;
        foreach ($results as $result) {
            if ($limit != 0) {
                if ($c == $limit) break;
            }
            self::printRow(
                (string) $result->_id,
                $result->fighter_name,
                $result->Height,
                $result->Weight,
                $result->Reach,
                $result->Stance,
                $result->DOB
            );
            $c++;
        }
    }

    public static function findByName($name): void
    {
        $results = self::$collection->find([
            "fighter_name" => $name,
        ]);

        self::printHeader();
        foreach ($results as $result) {
            self::printRow(
                (string) $result['_id'],
                $result['fighter_name'],
                $result['Height'],
                $result['Weight'],
                $result['Reach'],
                $result['Stance'],
                $result['DOB']
            );
        }
    }
    
    public static function findByHash($hash): void
    {
        $results = self::$collection->find([
            "_id" => new \MongoDB\BSON\ObjectID($hash),
        ]);

        foreach ($results as $result) {
            self::printHeader();
            self::printRow(
                (string) $result['id'],
                $result['fighter_name'],
                $result['Height'],
                $result['Weight'],
                $result['Reach'],
                $result['Stance'],
                $result['DOB']
            );
        }
    }

    public static function insert(
        $fighter_name,
        $Height,
        $Weight,
        $Reach,
        $Stance,
        $DOB
    ) { 
        self::$collection->insertOne([
            'fighter_name' => $fighter_name,
            'Height' => Converter::cmToFeet($Height),
            'Weight' => Converter::kgToLb($Weight),
            'Reach' => Converter::cmToFeet($Reach, true),
            'Stance' => $Stance,
            'DOB' => Converter::europeDateToAmerican($DOB)
        ]);
        self::$io->out("Done\n");
    }
}
