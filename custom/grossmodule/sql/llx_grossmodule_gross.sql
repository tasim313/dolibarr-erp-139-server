-- Copyright (C) ---Put here your own copyright and developer email---
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

CREATE TABLE llx_grossmodule_gross (
    rowid SERIAL PRIMARY KEY,
    ref VARCHAR(128) DEFAULT '(PROV)' NOT NULL,
    label VARCHAR(255),
    amount DOUBLE PRECISION DEFAULT NULL,
    qty REAL,
    fk_soc INTEGER,
    fk_project INTEGER,
    description TEXT,
    note_public TEXT,
    note_private TEXT,
    date_creation TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    tms TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    fk_user_creat INTEGER NOT NULL,
    fk_user_modif INTEGER,
    last_main_doc VARCHAR(255),
    import_key VARCHAR(14),
    model_pdf VARCHAR(255),
    status INTEGER NOT NULL
);