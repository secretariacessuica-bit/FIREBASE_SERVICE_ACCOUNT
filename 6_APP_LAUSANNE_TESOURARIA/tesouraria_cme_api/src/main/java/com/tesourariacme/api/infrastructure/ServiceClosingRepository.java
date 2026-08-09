package com.tesourariacme.api.infrastructure;

import com.tesourariacme.api.domain.ServiceClosing;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;
import java.util.List;

@Repository
public interface ServiceClosingRepository extends JpaRepository<ServiceClosing, Long> {
    List<ServiceClosing> findAllByOrderByServiceDateDesc();
}
