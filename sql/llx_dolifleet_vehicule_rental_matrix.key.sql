ALTER TABLE llx_dolifleet_vehicule_rental_matrix ADD UNIQUE matrix_unicity (fk_soc, fk_c_type_vh, fk_c_mark_vh, delay);
