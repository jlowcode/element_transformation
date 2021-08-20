<?php
/**
 *  Execute a structure that will transform a field (string, string multivalue, and DropDown multivalue) to fields (DropDown and Databasejoin).
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element_transformation.php
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 *  Perform a transformation of the fields that are in the database and dropdown / databasejoin.
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element_transformation.php
 * @since       3.0
 */
class PlgFabrik_ListElement_transformation extends plgFabrik_List
{
    public function button()
    {
        $model = $this->getModel();
        $field_lists = $this->fieldTableLists($model);

        $field_params = json_decode($field_lists->params);

        if (($field_params->element_transformation_able === "0") || ($field_params->element_transformation_able === 0)) {
            $params = $this->getParams();

            $joinModel = JModelLegacy::getInstance('Join', 'FabrikFEModel');
            $joinModelDest = JModelLegacy::getInstance('Join', 'FabrikFEModel');

            $field_type = $params->get('element_transformation_type');
            $field_delimit = $params->get('element_transformation_delimiter');
            $source = explode('.', $params->get('element_transformation_field'));
            $destiny = explode('.', $params->get('element_transformation_field_element'));

            $table_source = $destiny[0];
            $field_source = $source[1];
            $field_dest = $destiny[1];

            $listId = $model->getId();

            $field_elements = $this->fieldParamsTableElement($model, $table_source, $field_dest, $listId);
            $element = json_decode($field_elements->params);

            $elem_options = $element->sub_options;

            switch ($field_type) {
                case "1":
                    $list_target = $this->fieldTableListsTarget($model, $element->join_db_name);
                    $elements_target = $this->fieldParamsTableElement($model, $element->join_db_name, $element->join_val_col_synchronism, $list_target->id);
                    $par_element = json_decode($elements_target->params);

                    if (($field_elements->plugin === 'databasejoin') && (strlen($element->join_val_col_synchronism) === 0) && ($element->database_join_display_type === 'dropdown')) {

                        $data = $this->dataTableSource($model, $table_source, $field_source, $field_dest);

                        if (count($data) !== 0) {
                            foreach ($data as $value) {
                                $data_dest = $this->existDataTableTaget($model, $element->join_db_name, $element->join_val_column, $value[$field_source]);

                                if (count($data_dest) === 0) {
                                    $this->insertDataTableTargetUpdateTableDropdown($model, $table_source, $element->join_db_name, $field_dest, $element->join_val_column, $value['id'], $value[$field_source]);
                                } else {
                                    if ($data_dest[$element->join_key_column] !== $value[$field_dest]) {
                                        $this->updateDataTableSource($model, $table_source, $field_dest, $value['id'], $data_dest[$element->join_key_column]);
                                    }
                                }
                            }
                        }
                    } elseif (($field_elements->plugin === 'databasejoin') && (strlen($element->join_val_col_synchronism) !== 0) && ($element->database_join_display_type === 'dropdown') &&
                        (($par_element->database_join_display_type === 'multilist') || ($par_element->database_join_display_type === 'checkbox'))) {

                        $data = $this->dataTableSource($model, $table_source, $field_source, $field_dest);

                        $this->join = $joinModel->getJoinFromKey('element_id', $elements_target->id);

                        if (count($data) !== 0) {
                            foreach ($data as $value) {
                                $data_dest = $this->existDataTableTaget($model, $element->join_db_name, $element->join_val_column, $value[$field_source]);

                                if (count($data_dest) === 0) {
                                    $this->insertDataTableTargetMultilistUpdateTableDropdownInsertTableMultilist($model, $table_source, $element->join_db_name, $field_dest, $element->join_val_column, $value['id'], $value[$field_source], $this->join);
                                } else {
                                    $this->updateTableDropdownInsertTableRepeattMultilist($model, $table_source, $field_dest, $value['id'], $data_dest[$element->join_key_column], $this->join);
                                }
                            }
                        }
                    } elseif (($field_elements->plugin === 'databasejoin') && (strlen($element->join_val_col_synchronism) === 0) &&
                        (($element->database_join_display_type === 'multilist') || ($element->database_join_display_type === 'checkbox'))) {

                        $data = $this->dataTableSource($model, $table_source, $field_source, $field_dest);

                        $this->join = $joinModel->getJoinFromKey('element_id', $field_elements->id);

                        if (count($data) !== 0) {
                            foreach ($data as $value) {
                                $data_dest = $this->existDataTableTaget($model, $element->join_db_name, $element->join_val_column, $value[$field_source]);
                                if (count($data_dest) === 0) {
                                    $this->insertDataTableTargetInsertTableSourceMultilist($model, $element->join_db_name, $element->join_val_column, $value['id'], $value[$field_source], $this->join);
                                } else {
                                    $this->insertTableSourceMultilist($model, $value['id'], $data_dest[$element->join_key_column], $this->join);
                                }
                            }
                        }
                    } elseif (($field_elements->plugin === 'databasejoin') && (strlen($element->join_val_col_synchronism) !== 0) &&
                        (($element->database_join_display_type === 'multilist') || ($element->database_join_display_type === 'checkbox')) &&
                        ($par_element->database_join_display_type === 'dropdown')) {
                        $data = $this->dataTableSource($model, $table_source, $field_source, $field_dest);

                        $this->join = $joinModel->getJoinFromKey('element_id', $field_elements->id);

                        if (count($data) !== 0) {
                            foreach ($data as $value) {
                                $data_dest = $this->existDataTableTaget($model, $element->join_db_name, $element->join_val_column, $value[$field_source]);

                                if (count($data_dest) === 0) {
                                    $this->insertDataTableTargetInsertTableSourceDropdown($model, $element->join_db_name, $element->join_val_column, $value['id'], $value[$field_source], $this->join, $element->join_val_col_synchronism);
                                } else {
                                    $this->insertTableSourceMultilist($model, $value['id'], $data_dest[$element->join_key_column], $this->join);
                                }
                            }
                        }


                    } elseif (($field_elements->plugin === 'databasejoin') && (strlen($element->join_val_col_synchronism) !== 0) &&
                        (($element->database_join_display_type === 'multilist') || ($element->database_join_display_type === 'checkbox')) &&
                        (($par_element->database_join_display_type === 'multilist') || ($par_element->database_join_display_type === 'checkbox'))) {

                        $data = $this->dataTableSource($model, $table_source, $field_source, $field_dest);

                        $this->join = $joinModel->getJoinFromKey('element_id', $field_elements->id);
                        $this->join_target = $joinModelDest->getJoinFromKey('element_id', $elements_target->id);

                        if (count($data) !== 0) {
                            foreach ($data as $value) {
                                $data_dest = $this->existDataTableTaget($model, $element->join_db_name, $element->join_val_column, $value[$field_source]);

                                if (count($data_dest) === 0) {
                                    $this->insertDataTableTargetInsertTableSourceMultilistInsertTableTargetMultilist($model, $element->join_db_name, $element->join_val_column, $value['id'], $value[$field_source], $this->join, $this->join_target);
                                } else {
                                    $this->insertTableSourceMultilistInsertTableTargetMultilist($model, $value['id'], $data_dest[$element->join_key_column], $this->join, $this->join_target);
                                }
                            }
                        }
                    }
                    break;
                case "2":
                    if ($field_elements->plugin === 'dropdown') {
                        $data = $this->dataTableSource($model, $table_source, $field_source, $field_dest);

                        if (count($data) !== 0) {

                            foreach ($data as $key => $value) {
                                $ar_string = explode($field_delimit, $value[$field_source]);
                                $text_dropdown = '';

                                $total = count($ar_string) - 1;

                                foreach ($ar_string as $key1 => $ar_value) {

                                    if (!(in_array($ar_value, $elem_options->sub_values)) && !(in_array($ar_value, $elem_options->sub_labels))) {
                                        $elem_options->sub_values[] = ucfirst(strtolower($ar_value));
                                        $elem_options->sub_labels[] = ucfirst(strtolower($ar_value));
                                    }

                                    if (($total >= 1) && ($key1 < $total)) {
                                        $text_dropdown .= ucfirst(strtolower($ar_value)) . "\",\"";

                                    } elseif ($total === $key1) {
                                        $text_dropdown .= ucfirst(strtolower($ar_value));

                                    } elseif ($total == 0) {
                                        $text_dropdown = ucfirst(strtolower($ar_value));
                                    }
                                }

                                $num_exp = explode('","', $text_dropdown);

                                if (count($num_exp) !== 1) {
                                    $result_text = "[\"" . $text_dropdown . "\"]";
                                } else {
                                    $result_text = $text_dropdown;
                                }

                                $this->updateDataTableSourceUpdateTableElement($model, $table_source, $field_dest, $value['id'], $field_elements->id, $result_text, $element);
                            }
                        }
                    }
                    break;
                case "3":
                    $list_target = $this->fieldTableListsTarget($model, $element->join_db_name);
                    $elements_target = $this->fieldParamsTableElement($model, $element->join_db_name, $element->join_val_col_synchronism, $list_target->id);
                    $par_element = json_decode($elements_target->params);

                    if (($field_elements->plugin === 'databasejoin') && (strlen($element->join_val_col_synchronism) === 0) &&
                        (($element->database_join_display_type === 'multilist') || ($element->database_join_display_type === 'checkbox'))) {

                        $data = $this->dataTableSource($model, $table_source, $field_source, $field_dest);

                        $this->join = $joinModel->getJoinFromKey('element_id', $field_elements->id);

                        if (count($data) !== 0) {
                            foreach ($data as $value) {
                                $ar_string = explode($field_delimit, $value[$field_source]);

                                foreach ($ar_string as $ar_value) {
                                    $data_dest = $this->existDataTableTaget($model, $element->join_db_name, $element->join_val_column, $ar_value);

                                    if (count($data_dest) === 0) {
                                        $this->insertDataTableTargetInsertTableSourceMultilist($model, $element->join_db_name, $element->join_val_column, $value['id'], $ar_value, $this->join);
                                    } else {
                                        $this->insertTableSourceMultilist($model, $value['id'], $data_dest[$element->join_key_column], $this->join);
                                    }
                                }
                            }

                        }

                    } elseif (($field_elements->plugin === 'databasejoin') && (strlen($element->join_val_col_synchronism) !== 0) &&
                        (($element->database_join_display_type === 'multilist') || ($element->database_join_display_type === 'checkbox')) &&
                        (($par_element->database_join_display_type === 'multilist') || ($par_element->database_join_display_type === 'checkbox'))) {

                        $data = $this->dataTableSource($model, $table_source, $field_source, $field_dest);

                        $this->join = $joinModel->getJoinFromKey('element_id', $field_elements->id);
                        $this->join_target = $joinModelDest->getJoinFromKey('element_id', $elements_target->id);

                        if (count($data) !== 0) {
                            foreach ($data as $value) {
                                $ar_string = explode($field_delimit, $value[$field_source]);

                                foreach ($ar_string as $ar_value) {
                                    $data_dest = $this->existDataTableTaget($model, $element->join_db_name, $element->join_val_column, $ar_value);

                                    if (count($data_dest) === 0) {
                                        $this->insertDataTableTargetInsertTableSourceMultilistInsertTableTargetMultilist($model, $element->join_db_name, $element->join_val_column, $value['id'], $ar_value, $this->join, $this->join_target);
                                    } else {
                                        $this->insertTableSourceMultilistInsertTableTargetMultilist($model, $value['id'], $data_dest[$element->join_key_column], $this->join, $this->join_target);
                                    }
                                }
                            }

                        }
                    }
                    break;
                case "4":
                    $list_target = $this->fieldTableListsTarget($model, $element->join_db_name);
                    $elements_target = $this->fieldParamsTableElement($model, $element->join_db_name, $element->join_val_col_synchronism, $list_target->id);
                    $par_element = json_decode($elements_target->params);

                    $ar_simbol = Array('["', '"]');

                    if (($field_elements->plugin === 'databasejoin') && (strlen($element->join_val_col_synchronism) === 0) &&
                        (($element->database_join_display_type === 'multilist') || ($element->database_join_display_type === 'checkbox'))) {

                        $data = $this->dataTableSource($model, $table_source, $field_source, $field_dest);

                        $this->join = $joinModel->getJoinFromKey('element_id', $field_elements->id);

                        if (count($data) !== 0) {
                            foreach ($data as $value) {
                                $ar_exp = explode('","',str_replace($ar_simbol, "", $value[$field_source]));

                                foreach ($ar_exp as $ar_value) {
                                    $data_dest = $this->existDataTableTaget($model, $element->join_db_name, $element->join_val_column, $ar_value);

                                    if (count($data_dest) === 0) {
                                        $this->insertDataTableTargetInsertTableSourceMultilist($model, $element->join_db_name, $element->join_val_column, $value['id'], $ar_value, $this->join);
                                    } else {
                                        $this->insertTableSourceMultilist($model, $value['id'], $data_dest[$element->join_key_column], $this->join);
                                    }

                                }
                            }
                        }
                    }elseif (($field_elements->plugin === 'databasejoin') && (strlen($element->join_val_col_synchronism) !== 0) &&
                        (($element->database_join_display_type === 'multilist') || ($element->database_join_display_type === 'checkbox')) &&
                        (($par_element->database_join_display_type === 'multilist') || ($par_element->database_join_display_type === 'checkbox'))) {

                        $data = $this->dataTableSource($model, $table_source, $field_source, $field_dest);

                        $this->join = $joinModel->getJoinFromKey('element_id', $field_elements->id);
                        $this->join_target = $joinModelDest->getJoinFromKey('element_id', $elements_target->id);

                        if (count($data) !== 0) {
                            foreach ($data as $value) {
                                $ar_exp = explode('","',str_replace($ar_simbol, "", $value[$field_source]));

                                foreach ($ar_exp as $ar_value) {
                                    $data_dest = $this->existDataTableTaget($model, $element->join_db_name, $element->join_val_column, $ar_value);

                                    if (count($data_dest) === 0) {
                                        $this->insertDataTableTargetInsertTableSourceMultilistInsertTableTargetMultilist($model, $element->join_db_name, $element->join_val_column, $value['id'], $ar_value, $this->join, $this->join_target);
                                    } else {
                                        $this->insertTableSourceMultilistInsertTableTargetMultilist($model, $value['id'], $data_dest[$element->join_key_column], $this->join, $this->join_target);
                                    }
                                }
                            }

                        }
                    }
                    break;
            }

            $this->clearParamsPlugins($model);
        }

        return false;
    }

    /**
     * Method that disables the plug-in so that it does not run more than once, besides disabling it still takes it out.
     *
     * @param $model
     * @return bool
     */
    public function clearParamsPlugins($model)
    {
        $app = JFactory::getApplication();
        $db = $model->getDb();

        $listId = $model->getId();

        $result = $this->fieldTableLists($model);

        $params = json_decode($result->params);

        $index = array_search('element_transformation', $params->plugins);

        $params->plugin_state[$index] = "0";
        $params->element_transformation_able = "1";

        $paramsDB = json_encode($params);

        try {
            $db->transactionStart();

            $query = "UPDATE `#__fabrik_lists`
                    SET
                    `params` = '{$db->escape($paramsDB)}'
                    WHERE `id` = {$listId};";

            $db->setQuery($query);
            $db->execute();

            $db->transactionCommit();

            $app->enqueueMessage(JText::_(PLG_LIST_ELEMENT_TRANSFORMATION_MENSSAGEM_SUCCESS), 'Success');

        } catch (Exception $exc) {
            $db->transactionRollback();
        }

        $app->redirect(JUri::base() .'index.php?option=com_fabrik&task=list.view&listid=' . $listId);

        return false;
    }

    /**
     * Method that fetches all fields from the fabrik lists table
     *
     * @param $model
     * @return mixed
     */
    public function fieldTableLists($model)
    {
        $db = $model->getDb();

        $listId = $model->getId();

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__fabrik_lists'))
            ->Where($db->quoteName('id') . '=' . $listId);

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Method that takes data from the destination list.
     *
     * @param $model
     * @param $table
     * @return mixed
     */
    public function fieldTableListsTarget($model, $table)
    {
        $db = $model->getDb();

        $query = "SELECT
                    list.id,
                    list.label,
                    list.form_id,
                    list.db_table_name,
                    list.db_primary_key,
                    list.params
                  FROM #__fabrik_lists AS list 
                  WHERE list.db_table_name = '{$table}'";

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Method that brings the params field from the fabrik element table.
     *
     * @param $model
     * @return mixed
     */
    public function fieldParamsTableElement($model, $table, $field, $listId)
    {
        $db = $model->getDb();

        $query = "SELECT
                    element.id,
                    element.group_id,
                    element.`plugin`,
                    element.label,
                    element.params
                    FROM
                    #__fabrik_elements AS element
                    LEFT JOIN #__fabrik_formgroup AS `group` ON element.group_id = `group`.group_id
                    LEFT JOIN #__fabrik_lists AS list ON `group`.form_id = list.form_id
                    WHERE
                    element.`name` = '{$field}' AND
                    list.id = {$listId} AND
                    list.db_table_name = '{$table}';";

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Method that takes all data from the originating table so that it can transform the elements.
     *
     * @param $model
     * @param $table
     * @param $field_source
     * @param $field_dest
     * @return mixed
     */
    public function dataTableSource($model, $table, $field_source, $field_dest)
    {
        $db = $model->getDb();

        $query = "SELECT
                `table`.id,
                `table`.{$field_source},
                `table`.{$field_dest}
                FROM {$table} AS `table`;";

        $db->setQuery($query);

        return $db->loadAssocList();
    }

    /**
     * Method to check if it exists and bring data from target table by like search condition.
     *
     * @param $model
     * @param $table
     * @param $field
     * @param $search
     * @return mixed
     */
    public function existDataTableTaget($model, $table, $field, $search)
    {
        $db = $model->getDb();

        $query = "SELECT
                `table`.id,
                `table`.{$field}
                FROM {$table} AS `table`
                WHERE
                `table`.{$field} LIKE '{$search}';";

        $db->setQuery($query);

        return $db->loadAssoc();
    }

    /**
     * Method that changes the source table field to the information that is returning from the destination table.
     *
     * @param $model
     * @param $table
     * @param $field_dest
     * @param $data
     * @return bool
     */
    public function updateDataTableSource($model, $table, $field_dest, $id, $data)
    {
        $db = $model->getDb();

        try {
            $db->transactionStart();

            $query = $db->getQuery(true)
                ->update($db->quoteName($table))
                ->set($db->quoteName($field_dest) . ' = ' . $data)
                ->where($db->quoteName('id') . ' = ' . $id);

            $db->setQuery($query);
            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that inserts into target table and alters source table that is field databasejoin - dropdwn.
     *
     * @param $model
     * @param $table
     * @param $table_dest
     * @param $field
     * @param $field_dest
     * @param $data
     * @param $data_dest
     * @return bool
     */
    public function insertDataTableTargetUpdateTableDropdown($model, $table, $table_dest, $field, $field_dest, $data, $data_dest)
    {
        $db = $model->getDb();

        date_default_timezone_set('America/Sao_Paulo');
        $date = date("Y-m-d H:i:s");

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_dest}` (`date_time`, `{$field_dest}`) VALUES ('{$date}','{$db->escape($data_dest)}');";

            $db->setQuery($query);
            $db->execute();

            $id = $db->insertid();

            $query1 = $db->getQuery(true)
                ->update($db->quoteName($table))
                ->set($db->quoteName($field) . ' = ' . $id)
                ->where($db->quoteName('id') . ' = ' . $data);

            $db->setQuery($query1);
            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that inserts into the target table and alters source table that is field databasejoin - dropdwn and inserts into the source table repeat that makes the relationship with the target table.
     *
     * @param $model
     * @param $table
     * @param $table_dest
     * @param $field
     * @param $field_dest
     * @param $data
     * @param $data_dest
     * @param $table_Mult
     * @return bool
     */
    public function insertDataTableTargetMultilistUpdateTableDropdownInsertTableMultilist($model, $table, $table_dest, $field, $field_dest, $data, $data_dest, $table_Mult)
    {
        $db = $model->getDb();

        date_default_timezone_set('America/Sao_Paulo');
        $date = date("Y-m-d H:i:s");

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_dest}` (`date_time`, `{$field_dest}`) VALUES ('{$date}','{$db->escape($data_dest)}');";

            $db->setQuery($query);
            $db->execute();

            $id = $db->insertid();

            $query1 = $db->getQuery(true)
                ->update($db->quoteName($table))
                ->set($db->quoteName($field) . ' = ' . $id)
                ->where($db->quoteName('id') . ' = ' . $data);

            $db->setQuery($query1);
            $db->execute();

            $query2 = "INSERT INTO `{$table_Mult->table_join}` (`{$table_Mult->table_join_key}`, `{$table_Mult->table_key}`) VALUES ({$id},{$db->escape($data)})";

            $db->setQuery($query2);
            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that alters source table and inserts into target table repeat.
     *
     * @param $model
     * @param $table
     * @param $field
     * @param $data
     * @param $data_dest
     * @param $table_Mult
     * @return bool
     */
    public function updateTableDropdownInsertTableRepeattMultilist($model, $table, $field, $data, $data_dest, $table_Mult)
    {
        $db = $model->getDb();

        $query = "SELECT repit.id
                    FROM {$table_Mult->table_join} AS repit
                    WHERE repit.{$table_Mult->table_join_key} = {$data_dest} AND repit.{$table_Mult->table_key} = {$data};";

        $db->setQuery($query);

        $result = $db->loadObject();

        try {
            $db->transactionStart();

            $query1 = $db->getQuery(true)
                ->update($db->quoteName($table))
                ->set($db->quoteName($field) . ' = ' . $data_dest)
                ->where($db->quoteName('id') . ' = ' . $data);

            $db->setQuery($query1);
            $db->execute();

            if (count($result) === 0) {
                $query2 = "INSERT INTO `{$table_Mult->table_join}` (`{$table_Mult->table_join_key}`, `{$table_Mult->table_key}`) VALUES ({$data_dest},{$db->escape($data)})";

                $db->setQuery($query2);
                $db->execute();
            }

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that inserts into the target table and inserts into the source table repeat.
     *
     * @param $model
     * @param $table_dest
     * @param $field_dest
     * @param $data
     * @param $data_dest
     * @param $table_Mult
     * @return bool
     */
    public function insertDataTableTargetInsertTableSourceMultilist($model, $table_dest, $field_dest, $data, $data_dest, $table_Mult)
    {
        $db = $model->getDb();

        date_default_timezone_set('America/Sao_Paulo');
        $date = date("Y-m-d H:i:s");

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_dest}` (`date_time`, `{$field_dest}`) VALUES ('{$date}','{$db->escape($data_dest)}');";

            $db->setQuery($query);
            $db->execute();

            $id = $db->insertid();

            $query2 = "INSERT INTO `{$table_Mult->table_join}` (`{$table_Mult->table_join_key}`, `{$table_Mult->table_key}`) VALUES ({$data},{$db->escape($id)})";

            $db->setQuery($query2);
            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that inserts into the source table repeat.
     *
     * @param $model
     * @param $data
     * @param $data_dest
     * @param $table_Mult
     * @return bool
     */
    public function insertTableSourceMultilist($model, $data, $data_dest, $table_Mult)
    {
        $db = $model->getDb();

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_Mult->table_join}` (`{$table_Mult->table_join_key}`, `{$table_Mult->table_key}`) VALUES ({$data},{$db->escape($data_dest)})";

            $db->setQuery($query);
            $db->execute();

            $id = $db->insertid();

            $db->transactionCommit();

            return $id;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that inserts into the target table, inserts into the repeat source table, and inserts the inverted data into the repeat target table.
     *
     * @param $model
     * @param $table_dest
     * @param $field_dest
     * @param $data
     * @param $data_dest
     * @param $join
     * @param $join_target
     * @return bool
     */
    public function insertDataTableTargetInsertTableSourceMultilistInsertTableTargetMultilist($model, $table_dest, $field_dest, $data, $data_dest, $join, $join_target)
    {
        $db = $model->getDb();

        date_default_timezone_set('America/Sao_Paulo');
        $date = date("Y-m-d H:i:s");

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_dest}` (`date_time`, `{$field_dest}`) VALUES ('{$date}','{$db->escape($data_dest)}');";

            $db->setQuery($query);
            $db->execute();

            $id = $db->insertid();

            $query1 = "INSERT INTO `{$join->table_join}` (`{$join->table_join_key}`, `{$join->table_key}`) VALUES ({$data},{$db->escape($id)})";

            $db->setQuery($query1);
            $db->execute();

            $query2 = "INSERT INTO `{$join_target->table_join}` (`{$join_target->table_join_key}`, `{$join_target->table_key}`) VALUES ({$id},{$db->escape($data)})";

            $db->setQuery($query2);
            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that inserts into the source table repeat and inserts into the target table repeat.
     *
     * @param $model
     * @param $data
     * @param $data_dest
     * @param $join
     * @param $join_target
     * @return bool
     */
    public function insertTableSourceMultilistInsertTableTargetMultilist($model, $data, $data_dest, $join, $join_target)
    {
        $db = $model->getDb();

        try {
            $db->transactionStart();

            $query1 = "INSERT INTO `{$join->table_join}` (`{$join->table_join_key}`, `{$join->table_key}`) VALUES ({$data},{$db->escape($data_dest)})";

            $db->setQuery($query1);
            $db->execute();

            $query2 = "INSERT INTO `{$join_target->table_join}` (`{$join_target->table_join_key}`, `{$join_target->table_key}`) VALUES ({$data_dest},{$db->escape($data)})";

            $db->setQuery($query2);
            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that inserts into the target table and inserts into the Dropdown source table.
     *
     * @param $model
     * @param $table_dest
     * @param $field_dest
     * @param $data
     * @param $data_dest
     * @param $join
     * @param $field_sincro
     * @return bool
     */
    public function insertDataTableTargetInsertTableSourceDropdown($model, $table_dest, $field_dest, $data, $data_dest, $join, $field_sincro)
    {
        $db = $model->getDb();

        date_default_timezone_set('America/Sao_Paulo');
        $date = date("Y-m-d H:i:s");

        try {
            $db->transactionStart();

            $query = "INSERT INTO `{$table_dest}` (`date_time`, `{$field_dest}`, `{$field_sincro}`) VALUES ('{$date}','{$db->escape($data_dest)}', {$data});";

            $db->setQuery($query);
            $db->execute();

            $id = $db->insertid();

            $query1 = "INSERT INTO `{$join->table_join}` (`{$join->table_join_key}`, `{$join->table_key}`) VALUES ({$data},{$db->escape($id)})";

            $db->setQuery($query1);
            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }

    /**
     * Method that updates the source table and updates the Fabrik Elements table.
     *
     * @param $model
     * @param $table
     * @param $field_dest
     * @param $id
     * @param $id_element
     * @param $data
     * @param $element_params
     * @return bool
     */
    public function updateDataTableSourceUpdateTableElement($model, $table, $field_dest, $id, $id_element, $data, $element_params)
    {
        $db = $model->getDb();

        $paramsDB = json_encode($element_params);

        try {
            $db->transactionStart();

            $query = "UPDATE `{$table}`
                    SET
                    `{$field_dest}` = '{$db->escape($data)}'
                    WHERE `id` = {$id};";

            $db->setQuery($query);
            $db->execute();

            $query1 = "UPDATE `#__fabrik_elements`
                    SET
                    `params` = '{$db->escape($paramsDB)}'
                    WHERE `id` = {$id_element};";

            $db->setQuery($query1);
            $db->execute();

            $db->transactionCommit();

            return true;
        } catch (Exception $exc) {
            $db->transactionRollback();

            return false;
        }
    }
}