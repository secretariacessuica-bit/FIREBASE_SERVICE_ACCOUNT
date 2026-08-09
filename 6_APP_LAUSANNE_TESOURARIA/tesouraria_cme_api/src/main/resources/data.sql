-- Insere o fechamento principal (mock)
INSERT INTO service_closing (id, service_date, main_treasurer, co_treasurer, physical_total, unidentified_total, identified_total) 
VALUES (1, CURRENT_DATE, 'Admilson', 'Maria', 0, 0, 450.00);

-- Insere os envelopes (membros reais sugeridos)
INSERT INTO envelope (id, member_name, type, amount) VALUES (1, 'Jean Dupont', 'DIZIMO', 200.00);
INSERT INTO envelope (id, member_name, type, amount) VALUES (2, 'Marc Keller', 'OFERTA', 50.00);
INSERT INTO envelope (id, member_name, type, amount) VALUES (3, 'Lucía Gómez', 'DIZIMO', 200.00);

-- Relaciona os envelopes com o fechamento
INSERT INTO service_closing_identified_entries (service_closing_id, identified_entries_id) VALUES (1, 1);
INSERT INTO service_closing_identified_entries (service_closing_id, identified_entries_id) VALUES (1, 2);
INSERT INTO service_closing_identified_entries (service_closing_id, identified_entries_id) VALUES (1, 3);
